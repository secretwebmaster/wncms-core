<?php

namespace Wncms\Services\Automation;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LinkAutomationService
{
    /**
     * List links for read-only automation surfaces.
     *
     * The result is intentionally normalized so CLI, API, and future MCP
     * surfaces can share a stable payload shape.
     *
     * @param array $options
     * @return array
     */
    public function list(array $options = []): array
    {
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($options['per_page'] ?? 20)));
        $status = $this->normalizeStatus($options['status'] ?? 'active');

        $managerOptions = [
            'cache' => false,
            'page' => $page,
            'page_size' => $perPage,
            'sort' => (string) ($options['sort'] ?? 'id'),
            'direction' => (string) ($options['direction'] ?? 'desc'),
            'withs' => ['websites'],
        ];

        if ($status !== null) {
            $managerOptions['status'] = $status;
        }

        if ($this->hasValue($options['keyword'] ?? null)) {
            $managerOptions['keywords'] = (string) $options['keyword'];
        }

        if ($this->hasValue($options['website_id'] ?? null)) {
            $managerOptions['website_id'] = (int) $options['website_id'];
        }

        $links = wncms()->link()->getList($managerOptions);

        return [
            'items' => $this->normalizeListItems($links),
            'pagination' => $this->paginationMeta($links, $page, $perPage),
        ];
    }

    /**
     * Inspect one link by ID or slug.
     *
     * @param string|int $identifier
     * @param array $options
     * @return array|null
     */
    public function inspect(string|int $identifier, array $options = []): ?array
    {
        $link = $this->findLink($identifier, $options);
        if (!$link) {
            return null;
        }

        return $this->normalizeLink($link, true);
    }

    /**
     * Build a dry-run create mutation plan without writing to storage.
     *
     * @param array $input
     * @param array $options
     * @return array
     */
    public function planCreate(array $input, array $options = []): array
    {
        $uid = $this->generatedLinkUid($options);
        $attributes = $this->normalizeCreateAttributes($input, $uid);

        return $this->mutationPlan('create', $attributes, null, $input, $options, $this->validateMutation('create', $attributes));
    }

    /**
     * Create a link through the guarded automation path.
     *
     * @param array $input
     * @param array $options
     * @return array
     */
    public function create(array $input, array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false) || !$force;
        $options['write_mode'] = !$dryRun;
        $meta = $this->mutationResultMeta('create', $input, $options, $dryRun);
        $plan = $this->planCreate($input, $options);
        $websiteErrors = $this->lookupWebsiteErrors($options);

        if (!empty($websiteErrors)) {
            return AutomationResult::fail('Link create website validation failed.', [
                'plan' => $plan,
            ], $meta, $websiteErrors, 422);
        }

        if (($plan['validation']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link create validation failed.', [
                'plan' => $plan,
            ], $meta, (array) ($plan['validation']['errors'] ?? []), 422);
        }

        if (($plan['guard']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link create guard check failed.', [
                'plan' => $plan,
            ], $meta, (array) ($plan['guard']['errors'] ?? []), (int) ($plan['guard']['code'] ?? 403));
        }

        if ($dryRun) {
            return AutomationResult::success('Link create dry-run plan generated.', [
                'plan' => $plan,
            ], $meta, 202);
        }

        $actorResult = app(AutomationActorResolver::class)->resolve($options, true);
        $actor = $actorResult['model'] ?? null;
        if (!$actor instanceof Authenticatable) {
            return AutomationResult::fail('Link create actor resolution failed.', [
                'plan' => $plan,
            ], $meta, (array) ($actorResult['errors'] ?? ['actor' => ['invalid']]), (int) ($actorResult['code'] ?? 401));
        }

        $authGuard = Auth::guard();
        $previousActor = $authGuard->user();
        $authGuard->setUser($actor);

        try {
            $result = DB::transaction(function () use ($actor, $input, $options, $plan, $meta) {
                $request = Request::create('/', 'POST', $this->requestPayloadFromPlan($input, $plan));
                $request->setUserResolver(fn() => $actor);
                $hookValidationErrors = $this->validateStoreHooks($request);

                if (!empty($hookValidationErrors)) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link create hook validation failed.', [
                        'plan' => $plan,
                    ], $meta, $hookValidationErrors, 422));
                }

                $attributes = (array) ($plan['attributes'] ?? []);
                Event::dispatch('wncms.backend.links.store.attributes.before', [$request, &$attributes]);

                $modelClass = wncms()->getModelClass('link');
                $link = $modelClass::create($attributes);
                if (!$link->exists) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link create was cancelled.', [
                        'plan' => $plan,
                    ], $meta, [
                        'create' => ['cancelled'],
                    ], 409));
                }

                $this->syncMutationWebsites($link, (array) ($plan['relationships']['website_ids'] ?? []));
                $this->syncMutationTags($link, (array) ($plan['relationships']['tags'] ?? []));
                Event::dispatch('wncms.backend.links.store.after', [$link, $request]);

                $link->loadMissing(['websites']);
                $writtenPlan = $plan;
                $writtenPlan['dry_run'] = false;
                $writtenPlan['will_write'] = true;
                $writtenPlan['target'] = $this->normalizeLink($link, false);
                $writtenPlan['attributes'] = $attributes;
                $writtenPlan['audit'] = app(MutationAuditService::class)->previewFromPlan($writtenPlan, [
                    'surface' => (string) ($options['surface'] ?? 'service'),
                    'domain' => 'links',
                    'run_id' => $options['run_id'] ?? null,
                    'actor_type' => $writtenPlan['guard']['actor']['type'] ?? null,
                    'actor_id' => $writtenPlan['guard']['actor']['id'] ?? null,
                ]);

                $audit = app(MutationAuditService::class)->writeFromPlan($writtenPlan, [
                    'model_id' => (int) $link->getKey(),
                    'result_code' => 201,
                    'result_status' => 'success',
                    'message' => 'Link created.',
                ]);

                return AutomationResult::success('Link created.', [
                    'item' => $this->normalizeLink($link, true),
                    'plan' => $writtenPlan,
                    'audit' => [
                        'id' => (int) $audit->getKey(),
                    ],
                ], $meta, 201);
            });
        } catch (LinkMutationAbortException $exception) {
            $result = $exception->result();
        } finally {
            if ($previousActor instanceof Authenticatable) {
                $authGuard->setUser($previousActor);
            } else {
                $authGuard->forgetUser();
            }
        }

        if (($result['status'] ?? null) === 'success') {
            wncms()->cache()->flush(['links']);
        }

        return $result;
    }

    /**
     * Build a dry-run update mutation plan without writing to storage.
     *
     * @param string|int $identifier
     * @param array $input
     * @param array $options
     * @return array|null
     */
    public function planUpdate(string|int $identifier, array $input, array $options = []): ?array
    {
        $link = $this->findLink($identifier, $options);
        if (!$link) {
            return null;
        }

        $attributes = $this->normalizeUpdateAttributes($input);
        $changes = $this->attributeChanges($link, $attributes);
        $options['guard_website_ids'] = $this->websiteIds($link);

        return $this->mutationPlan('update', $attributes, $link, $input, $options, $this->validateMutation('update', $attributes), $changes);
    }

    /**
     * Build an atomic dry-run plan for multiple Link patches.
     *
     * @param  array  $items
     * @param  array  $options
     * @return array
     */
    public function planBulkUpdate(array $items, array $options = []): array
    {
        $writeMode = (bool) ($options['write_mode'] ?? false);
        $validationErrors = $this->bulkUpdateValidationErrors($items);
        $plannedItems = [];
        $websiteIds = [];

        if (empty($validationErrors)) {
            foreach ($items as $item) {
                $identifier = $item['identifier'];
                $link = $this->findLink($identifier, $options);
                if (!$link) {
                    $validationErrors['target'][] = (string) $identifier;
                    continue;
                }

                $attributes = $this->normalizeUpdateAttributes([
                    'url' => $item['url'] ?? null,
                    'sort' => $item['sort'] ?? null,
                ]);
                $attributes = array_intersect_key($attributes, array_flip(array_keys(array_diff_key($item, ['identifier' => true]))));
                $changes = $this->attributeChanges($link, $attributes);
                $targetWebsiteIds = $this->websiteIds($link);
                $websiteIds = array_merge($websiteIds, $targetWebsiteIds);
                $itemOptions = array_merge($options, ['guard_website_ids' => $targetWebsiteIds]);
                $itemPlan = $this->mutationPlan('update', $attributes, $link, $item, $itemOptions, $this->validateMutation('update', $attributes), $changes);
                $itemPlan['hooks'] = [];

                $plannedItems[] = [
                    'identifier' => $identifier,
                    'status' => empty($changes) ? 'noop' : 'change',
                    'plan' => $itemPlan,
                ];
            }
        }

        $targetIds = array_map(fn(array $plannedItem) => (int) ($plannedItem['plan']['target']['id'] ?? 0), $plannedItems);
        $duplicateIds = array_filter(array_count_values($targetIds), fn(int $count) => $count > 1);
        if (!empty($duplicateIds)) {
            $validationErrors['items'][] = 'duplicate_target';
        }

        $summary = [
            'requested' => count($items),
            'changed' => count(array_filter($plannedItems, fn(array $item) => $item['status'] === 'change')),
            'noop' => count(array_filter($plannedItems, fn(array $item) => $item['status'] === 'noop')),
        ];
        $plan = [
            'operation' => 'bulk_update',
            'atomic' => true,
            'dry_run' => !$writeMode,
            'will_write' => false,
            'model_key' => 'link',
            'items' => $plannedItems,
            'summary' => $summary,
            'relationships' => [
                'website_ids' => array_values(array_unique($websiteIds)),
            ],
            'safety' => [
                'permission' => 'link_edit',
                'actor_required' => true,
                'audit_required' => true,
                'audit_storage' => 'mutation_audits',
                'force_required_for_write' => true,
                'write_mode' => $writeMode ? 'guarded' : 'dry_run',
            ],
            'validation' => [
                'status' => empty($validationErrors) ? 'pass' : 'fail',
                'errors' => $validationErrors,
            ],
            'cache' => [
                'flush_tags' => ['links'],
            ],
            'hooks' => [],
            'notes' => [
                'The batch is atomic: no item is written unless every target, guard, and audit check passes.',
                'Bulk update intentionally dispatches no Link hooks.',
            ],
        ];
        $plan['guard'] = app(MutationGuardService::class)->preview($plan, [
            'write_mode' => $writeMode,
            'actor_user_id' => $options['actor_user_id'] ?? null,
            'actor_user' => $options['actor_user'] ?? null,
            'user_id' => $options['user_id'] ?? null,
        ]);

        return $plan;
    }

    /**
     * Atomically update multiple Links through the guarded automation path.
     *
     * Every target and permission check is repeated inside the transaction.
     *
     * @param  array  $items
     * @param  array  $options
     * @return array
     */
    public function bulkUpdate(array $items, array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false) || !$force;
        $options['write_mode'] = !$dryRun;
        $meta = $this->mutationResultMeta('bulk_update', ['items' => $items], $options, $dryRun);
        $websiteErrors = $this->lookupWebsiteErrors($options);

        if (!empty($websiteErrors)) {
            return AutomationResult::fail('Link bulk update website validation failed.', null, $meta, $websiteErrors, 422);
        }

        $plan = $this->planBulkUpdate($items, $options);
        if (($plan['validation']['status'] ?? 'fail') !== 'pass') {
            $errors = (array) ($plan['validation']['errors'] ?? []);
            $missingIdentifiers = (array) ($errors['target'] ?? []);
            if (!empty($missingIdentifiers)) {
                return AutomationResult::fail('Link not found.', ['plan' => $plan], $meta, [
                    'identifier' => $missingIdentifiers,
                ], 404);
            }

            return AutomationResult::fail('Link bulk update validation failed.', ['plan' => $plan], $meta, $errors, 422);
        }

        if (($plan['guard']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link bulk update guard check failed.', ['plan' => $plan], $meta, (array) ($plan['guard']['errors'] ?? []), (int) ($plan['guard']['code'] ?? 403));
        }

        if ($dryRun) {
            return AutomationResult::success('Link bulk update dry-run plan generated.', ['plan' => $plan], $meta, 202);
        }

        $actorResult = app(AutomationActorResolver::class)->resolve($options, true);
        $actor = $actorResult['model'] ?? null;
        if (!$actor instanceof Authenticatable) {
            return AutomationResult::fail('Link bulk update actor resolution failed.', ['plan' => $plan], $meta, (array) ($actorResult['errors'] ?? ['actor' => ['invalid']]), (int) ($actorResult['code'] ?? 401));
        }

        $authGuard = Auth::guard();
        $previousActor = $authGuard->user();
        $authGuard->setUser($actor);
        $runId = (string) ($options['run_id'] ?? Str::uuid());

        try {
            $result = DB::transaction(function () use ($actor, $items, $options, $plan, $meta, $runId) {
                $freshPlan = $this->planBulkUpdate($items, array_merge($options, [
                    'lock_for_update' => true,
                ]));
                if (($freshPlan['validation']['status'] ?? 'fail') !== 'pass') {
                    $errors = (array) ($freshPlan['validation']['errors'] ?? []);
                    $missingIdentifiers = (array) ($errors['target'] ?? []);

                    throw new BulkUpdateAbortException(AutomationResult::fail(!empty($missingIdentifiers) ? 'Link not found.' : 'Link bulk update validation failed.', [
                        'plan' => $freshPlan,
                    ], $meta, !empty($missingIdentifiers) ? ['identifier' => $missingIdentifiers] : $errors, !empty($missingIdentifiers) ? 404 : 422));
                }

                if (($freshPlan['guard']['status'] ?? 'fail') !== 'pass') {
                    throw new BulkUpdateAbortException(AutomationResult::fail('Link bulk update guard check failed.', ['plan' => $freshPlan], $meta, (array) ($freshPlan['guard']['errors'] ?? []), (int) ($freshPlan['guard']['code'] ?? 403)));
                }

                $plannedChanges = array_map(fn(array $item) => (array) ($item['plan']['changes'] ?? []), (array) $plan['items']);
                $freshChanges = array_map(fn(array $item) => (array) ($item['plan']['changes'] ?? []), (array) $freshPlan['items']);
                if ($plannedChanges !== $freshChanges) {
                    throw new BulkUpdateAbortException(AutomationResult::fail('Link bulk update became stale.', ['plan' => $freshPlan], $meta, [
                        'items' => ['stale'],
                    ], 409));
                }

                $audits = [];
                foreach ($freshPlan['items'] as $item) {
                    if (($item['status'] ?? null) !== 'change') {
                        continue;
                    }

                    $itemPlan = (array) $item['plan'];
                    $link = $this->findLink($item['identifier'], array_merge($options, [
                        'lock_for_update' => true,
                    ]));
                    if (!$link) {
                        throw new BulkUpdateAbortException(AutomationResult::fail('Link not found.', ['plan' => $freshPlan], $meta, [
                            'identifier' => [(string) $item['identifier']],
                        ], 404));
                    }

                    $changes = $this->attributeChanges($link, (array) $itemPlan['attributes']);
                    if ($changes !== (array) $itemPlan['changes']) {
                        throw new BulkUpdateAbortException(AutomationResult::fail('Link bulk update became stale.', ['plan' => $freshPlan], $meta, [
                            'items' => ['stale'],
                        ], 409));
                    }

                    if ($link->update((array) $itemPlan['attributes']) !== true) {
                        throw new BulkUpdateAbortException(AutomationResult::fail('Link bulk update was cancelled.', ['plan' => $freshPlan], $meta, [
                            'items' => ['cancelled'],
                        ], 409));
                    }

                    $itemPlan['operation'] = 'bulk_update';
                    $itemPlan['dry_run'] = false;
                    $itemPlan['will_write'] = true;
                    $itemPlan['hooks'] = [];
                    $itemPlan['target'] = $this->normalizeLink($link, true);
                    $itemPlan['audit'] = app(MutationAuditService::class)->previewFromPlan($itemPlan, [
                        'surface' => (string) ($options['surface'] ?? 'service'),
                        'domain' => 'links',
                        'run_id' => $runId,
                        'actor_type' => $itemPlan['guard']['actor']['type'] ?? null,
                        'actor_id' => $itemPlan['guard']['actor']['id'] ?? (int) $actor->getKey(),
                    ]);
                    $audit = app(MutationAuditService::class)->writeFromPlan($itemPlan, [
                        'model_id' => (int) $link->getKey(),
                        'result_code' => 200,
                        'result_status' => 'success',
                        'message' => 'Link bulk updated.',
                    ]);
                    $audits[] = (int) $audit->getKey();
                }

                $freshPlan['dry_run'] = false;
                $freshPlan['will_write'] = $freshPlan['summary']['changed'] > 0;

                return AutomationResult::success('Link bulk update completed.', [
                    'summary' => $freshPlan['summary'],
                    'items' => $freshPlan['items'],
                    'plan' => $freshPlan,
                    'run_id' => $runId,
                    'audit_ids' => $audits,
                ], $meta, 200);
            });
        } catch (BulkUpdateAbortException $exception) {
            $result = $exception->result();
        } finally {
            if ($previousActor instanceof Authenticatable) {
                $authGuard->setUser($previousActor);
            } else {
                $authGuard->forgetUser();
            }
        }

        if (($result['status'] ?? null) === 'success' && (($result['data']['summary']['changed'] ?? 0) > 0)) {
            wncms()->cache()->flush(['links']);
        }

        return $result;
    }

    /**
     * Update a link through the guarded automation path.
     *
     * Patch input only changes supplied attributes. Existing backend update
     * hooks receive the declared actor and the auth context is restored after.
     *
     * @param  string|int  $identifier
     * @param  array  $input
     * @param  array  $options
     * @return array
     */
    public function update(string|int $identifier, array $input, array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false) || !$force;
        $options['write_mode'] = !$dryRun;
        $meta = $this->mutationResultMeta('update', $input, $options, $dryRun);
        $websiteErrors = $this->lookupWebsiteErrors($options);

        if (!empty($websiteErrors)) {
            return AutomationResult::fail('Link update website validation failed.', null, $meta, $websiteErrors, 422);
        }

        $plan = $this->planUpdate($identifier, $input, $options);
        if (!$plan) {
            return AutomationResult::fail('Link not found.', null, $meta, [
                'identifier' => [(string) $identifier],
            ], 404);
        }

        if (($plan['validation']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link update validation failed.', [
                'plan' => $plan,
            ], $meta, (array) ($plan['validation']['errors'] ?? []), 422);
        }

        if (($plan['guard']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link update guard check failed.', [
                'plan' => $plan,
            ], $meta, (array) ($plan['guard']['errors'] ?? []), (int) ($plan['guard']['code'] ?? 403));
        }

        if ($dryRun) {
            return AutomationResult::success('Link update dry-run plan generated.', [
                'plan' => $plan,
            ], $meta, 202);
        }

        if (empty($plan['changes'])) {
            return AutomationResult::success('Link update skipped; no changes detected.', [
                'item' => $plan['target'],
                'plan' => $plan,
            ], $meta, 200);
        }

        $actorResult = app(AutomationActorResolver::class)->resolve($options, true);
        $actor = $actorResult['model'] ?? null;
        if (!$actor instanceof Authenticatable) {
            return AutomationResult::fail('Link update actor resolution failed.', [
                'plan' => $plan,
            ], $meta, (array) ($actorResult['errors'] ?? ['actor' => ['invalid']]), (int) ($actorResult['code'] ?? 401));
        }

        $authGuard = Auth::guard();
        $previousActor = $authGuard->user();
        $authGuard->setUser($actor);

        try {
            $result = DB::transaction(function () use ($actor, $identifier, $input, $options, $plan, $meta) {
                $freshOptions = array_merge($options, [
                    'lock_for_update' => true,
                ]);
                $freshPlan = $this->planUpdate($identifier, $input, $freshOptions);
                if (!$freshPlan) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link not found.', [
                        'plan' => $plan,
                    ], $meta, [
                        'identifier' => [(string) $identifier],
                    ], 404));
                }

                if (($freshPlan['guard']['status'] ?? 'fail') !== 'pass') {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update guard check failed.', [
                        'plan' => $freshPlan,
                    ], $meta, (array) ($freshPlan['guard']['errors'] ?? []), (int) ($freshPlan['guard']['code'] ?? 403)));
                }

                if (!$this->sameUpdateApproval($plan, $freshPlan)) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update became stale.', [
                        'plan' => $freshPlan,
                    ], $meta, [
                        'update' => ['stale'],
                    ], 409));
                }

                $link = $this->findLink($identifier, $freshOptions);

                $request = Request::create('/', 'PUT', $this->requestPayloadFromPlan($input, $plan));
                $request->setUserResolver(fn() => $actor);
                $hookValidationErrors = $this->validateUpdateHooks($link, $request);

                if (!empty($hookValidationErrors)) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update hook validation failed.', [
                        'plan' => $plan,
                    ], $meta, $hookValidationErrors, 422));
                }

                $revalidatedPlan = $this->planUpdate($identifier, $input, $freshOptions);
                if (!$revalidatedPlan) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link not found.', [
                        'plan' => $freshPlan,
                    ], $meta, [
                        'identifier' => [(string) $identifier],
                    ], 404));
                }

                if (($revalidatedPlan['guard']['status'] ?? 'fail') !== 'pass') {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update guard check failed.', [
                        'plan' => $revalidatedPlan,
                    ], $meta, (array) ($revalidatedPlan['guard']['errors'] ?? []), (int) ($revalidatedPlan['guard']['code'] ?? 403)));
                }

                if (!$this->sameUpdateApproval($plan, $revalidatedPlan)) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update became stale.', [
                        'plan' => $revalidatedPlan,
                    ], $meta, [
                        'update' => ['stale'],
                    ], 409));
                }

                $link = $this->findLink($identifier, $freshOptions);

                $attributes = (array) ($plan['attributes'] ?? []);
                Event::dispatch('wncms.backend.links.update.attributes.before', [$link, $request, &$attributes]);
                $writtenChanges = $this->attributeChanges($link, $attributes);
                if ($link->update($attributes) !== true) {
                    throw new LinkMutationAbortException(AutomationResult::fail('Link update was cancelled.', [
                        'plan' => $revalidatedPlan,
                    ], $meta, [
                        'update' => ['cancelled'],
                    ], 409));
                }
                Event::dispatch('wncms.backend.links.update.after', [$link, $request]);

                $link->loadMissing(['websites']);
                $writtenPlan = $plan;
                $writtenPlan['dry_run'] = false;
                $writtenPlan['will_write'] = true;
                $writtenPlan['target'] = $this->normalizeLink($link, false);
                $writtenPlan['attributes'] = $attributes;
                $writtenPlan['changes'] = $writtenChanges;
                $writtenPlan['audit'] = app(MutationAuditService::class)->previewFromPlan($writtenPlan, [
                    'surface' => (string) ($options['surface'] ?? 'service'),
                    'domain' => 'links',
                    'run_id' => $options['run_id'] ?? null,
                    'actor_type' => $writtenPlan['guard']['actor']['type'] ?? null,
                    'actor_id' => $writtenPlan['guard']['actor']['id'] ?? null,
                ]);

                $audit = app(MutationAuditService::class)->writeFromPlan($writtenPlan, [
                    'model_id' => (int) $link->getKey(),
                    'result_code' => 200,
                    'result_status' => 'success',
                    'message' => 'Link updated.',
                ]);

                return AutomationResult::success('Link updated.', [
                    'item' => $this->normalizeLink($link, true),
                    'changes' => $writtenChanges,
                    'plan' => $writtenPlan,
                    'audit' => [
                        'id' => (int) $audit->getKey(),
                    ],
                ], $meta, 200);
            });
        } catch (LinkMutationAbortException $exception) {
            $result = $exception->result();
        } finally {
            if ($previousActor instanceof Authenticatable) {
                $authGuard->setUser($previousActor);
            } else {
                $authGuard->forgetUser();
            }
        }

        if (($result['status'] ?? null) === 'success') {
            wncms()->cache()->flush(['links']);
        }

        return $result;
    }

    /**
     * Build a dry-run delete mutation plan without writing to storage.
     *
     * @param string|int $identifier
     * @param array $options
     * @return array|null
     */
    public function planDelete(string|int $identifier, array $options = []): ?array
    {
        $link = $this->findLink($identifier, $options);
        if (!$link) {
            return null;
        }

        $options['guard_website_ids'] = $this->websiteIds($link);

        return $this->mutationPlan('delete', [], $link, [], $options, []);
    }

    /**
     * Delete a link through the guarded automation path.
     *
     * The target snapshot is preserved in the mutation audit before deletion.
     * No Link delete hooks exist, so this path intentionally dispatches none.
     *
     * @param  string|int  $identifier
     * @param  array  $options
     * @return array
     */
    public function delete(string|int $identifier, array $options = []): array
    {
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false) || !$force;
        $options['write_mode'] = !$dryRun;
        $meta = $this->mutationResultMeta('delete', [], $options, $dryRun);
        $websiteErrors = $this->lookupWebsiteErrors($options);

        if (!empty($websiteErrors)) {
            return AutomationResult::fail('Link delete website validation failed.', null, $meta, $websiteErrors, 422);
        }

        $plan = $this->planDelete($identifier, $options);
        if (!$plan) {
            return AutomationResult::fail('Link not found.', null, $meta, [
                'identifier' => [(string) $identifier],
            ], 404);
        }

        if (($plan['guard']['status'] ?? 'fail') !== 'pass') {
            return AutomationResult::fail('Link delete guard check failed.', [
                'plan' => $plan,
            ], $meta, (array) ($plan['guard']['errors'] ?? []), (int) ($plan['guard']['code'] ?? 403));
        }

        if ($dryRun) {
            return AutomationResult::success('Link delete dry-run plan generated.', [
                'plan' => $plan,
            ], $meta, 202);
        }

        $actorResult = app(AutomationActorResolver::class)->resolve($options, true);
        $actor = $actorResult['model'] ?? null;
        if (!$actor instanceof Authenticatable) {
            return AutomationResult::fail('Link delete actor resolution failed.', [
                'plan' => $plan,
            ], $meta, (array) ($actorResult['errors'] ?? ['actor' => ['invalid']]), (int) ($actorResult['code'] ?? 401));
        }

        $authGuard = Auth::guard();
        $previousActor = $authGuard->user();
        $authGuard->setUser($actor);

        try {
            $result = DB::transaction(function () use ($actor, $options, $plan, $meta) {
                $link = $this->findLink($plan['target']['id'], $options);

                if (!$link) {
                    return AutomationResult::fail('Link not found.', [
                        'plan' => $plan,
                    ], $meta, [
                        'identifier' => [(string) ($plan['target']['id'] ?? '')],
                    ], 404);
                }

                $writtenPlan = $plan;
                $writtenPlan['dry_run'] = false;
                $writtenPlan['will_write'] = true;
                $writtenPlan['target'] = $this->normalizeLink($link, true);
                $writtenPlan['relationships']['website_ids'] = $this->websiteIds($link);
                $writtenPlan['guard'] = app(MutationGuardService::class)->preview($writtenPlan, [
                    'write_mode' => true,
                    'actor_user_id' => (int) $actor->getKey(),
                ]);

                if (($writtenPlan['guard']['status'] ?? 'fail') !== 'pass') {
                    return AutomationResult::fail('Link delete guard check failed.', [
                        'plan' => $writtenPlan,
                    ], $meta, (array) ($writtenPlan['guard']['errors'] ?? []), (int) ($writtenPlan['guard']['code'] ?? 403));
                }

                $writtenPlan['audit'] = app(MutationAuditService::class)->previewFromPlan($writtenPlan, [
                    'surface' => (string) ($options['surface'] ?? 'service'),
                    'domain' => 'links',
                    'run_id' => $options['run_id'] ?? null,
                    'actor_type' => $writtenPlan['guard']['actor']['type'] ?? null,
                    'actor_id' => $writtenPlan['guard']['actor']['id'] ?? null,
                ]);

                $deleted = $this->normalizeLink($link, true);
                if ($link->delete() !== true) {
                    return AutomationResult::fail('Link delete was cancelled.', [
                        'plan' => $writtenPlan,
                    ], $meta, [
                        'delete' => ['cancelled'],
                    ], 409);
                }

                $audit = app(MutationAuditService::class)->writeFromPlan($writtenPlan, [
                    'model_id' => (int) $deleted['id'],
                    'result_code' => 200,
                    'result_status' => 'success',
                    'message' => 'Link deleted.',
                ]);

                return AutomationResult::success('Link deleted.', [
                    'deleted' => $deleted,
                    'plan' => $writtenPlan,
                    'audit' => [
                        'id' => (int) $audit->getKey(),
                    ],
                ], $meta, 200);
            });
        } finally {
            if ($previousActor instanceof Authenticatable) {
                $authGuard->setUser($previousActor);
            } else {
                $authGuard->forgetUser();
            }
        }

        if (($result['status'] ?? null) === 'success') {
            wncms()->cache()->flush(['links']);
        }

        return $result;
    }

    /**
     * Find a link by ID or slug.
     *
     * @param string|int $identifier
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function findLink(string|int $identifier, array $options = []): ?Model
    {
        $modelClass = wncms()->getModelClass('link');
        $query = $modelClass::query()->with(['websites']);
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        if (is_numeric($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        if ($this->hasValue($options['website_id'] ?? null) && method_exists($modelClass, 'applyWebsiteScope')) {
            $modelClass::applyWebsiteScope($query, (int) $options['website_id']);
        }

        if ((bool) ($options['lock_for_update'] ?? false)) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Normalize attributes for a create dry-run.
     *
     * @param array $input
     * @param string $uid
     * @return array
     */
    protected function normalizeCreateAttributes(array $input, string $uid): array
    {
        return [
            'status' => $this->mutationString($input['status'] ?? 'active'),
            'tracking_code' => $this->mutationString($input['tracking_code'] ?? $uid),
            'slug' => $this->mutationString($input['slug'] ?? $uid),
            'name' => $this->mutationValue($input['name'] ?? null),
            'url' => $this->mutationString($input['url'] ?? null),
            'slogan' => $this->mutationValue($input['slogan'] ?? null),
            'description' => $this->mutationValue($input['description'] ?? null),
            'external_thumbnail' => $this->mutationString($input['external_thumbnail'] ?? null),
            'remark' => $this->mutationString($input['remark'] ?? null),
            'sort' => $this->mutationInteger($input['sort'] ?? null),
            'color' => $this->mutationString($input['color'] ?? null),
            'background' => $this->mutationString($input['background'] ?? null),
            'is_pinned' => $this->mutationBoolean($input['is_pinned'] ?? false),
            'is_recommended' => $this->mutationBoolean($input['is_recommended'] ?? false),
            'expired_at' => $this->mutationString($input['expired_at'] ?? null),
            'hit_at' => $this->mutationString($input['hit_at'] ?? null),
            'clicks' => $this->mutationInteger($input['clicks'] ?? 0),
            'contact' => $this->mutationString($input['contact'] ?? null),
        ];
    }

    /**
     * Normalize patch-style attributes for an update dry-run.
     *
     * @param array $input
     * @return array
     */
    protected function normalizeUpdateAttributes(array $input): array
    {
        $attributes = [];

        foreach ($this->mutationAttributeTypes() as $field => $type) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $attributes[$field] = match ($type) {
                'bool' => $this->validMutationBoolean($input[$field])
                    ? $this->mutationBoolean($input[$field])
                    : $input[$field],
                'int' => $this->mutationInteger($input[$field]),
                'value' => $this->mutationNullableValue($input[$field]),
                default => $this->mutationString($input[$field]),
            };
        }

        return $attributes;
    }

    /**
     * Build the shared mutation plan payload.
     *
     * @param string $operation
     * @param array $attributes
     * @param \Illuminate\Database\Eloquent\Model|null $target
     * @param array $input
     * @param array $options
     * @param array $errors
     * @param array $changes
     * @return array
     */
    protected function mutationPlan(string $operation, array $attributes, ?Model $target, array $input, array $options, array $errors, array $changes = []): array
    {
        $writeMode = (bool) ($options['write_mode'] ?? false);
        $notes = in_array($operation, ['create', 'update', 'delete'], true)
            ? [ucfirst($operation) . ' writes are available through guarded automation when actor, permission, website scope, and audit checks pass.']
            : ['Real writes for this operation are still pending; use the dry-run plan as v7 implementation input.'];

        if (!$writeMode) {
            $notes[] = 'Dry-run mode does not write records. Pass --force with an allowed actor to mutate storage.';
        }

        if ($operation === 'update' && empty($changes)) {
            $notes[] = 'No attribute changes detected in the supplied patch input.';
        }

        $plan = [
            'operation' => $operation,
            'dry_run' => !$writeMode,
            'will_write' => false,
            'model_key' => 'link',
            'target' => $target ? $this->normalizeLink($target, false) : null,
            'attributes' => $attributes,
            'changes' => $changes,
            'relationships' => [
                'website_ids' => array_key_exists('guard_website_ids', $options)
                    ? $this->normalizeIdList($options['guard_website_ids'])
                    : $this->mutationWebsiteIds($input, $options),
                'tags' => $this->mutationTags($input),
                'media' => $this->mutationMedia($input),
            ],
            'safety' => [
                'permission' => $this->mutationPermission($operation),
                'actor_required' => true,
                'audit_required' => true,
                'audit_storage' => 'mutation_audits',
                'force_required_for_write' => true,
                'write_mode' => $writeMode ? 'guarded' : 'dry_run',
            ],
            'validation' => [
                'status' => empty($errors) ? 'pass' : 'fail',
                'errors' => $errors,
            ],
            'cache' => [
                'flush_tags' => ['links'],
            ],
            'hooks' => $this->mutationHooks($operation),
            'notes' => $notes,
        ];

        $plan['guard'] = app(MutationGuardService::class)->preview($plan, [
            'write_mode' => $writeMode,
            'actor_user_id' => $options['actor_user_id'] ?? null,
            'actor_user' => $options['actor_user'] ?? null,
            'user_id' => $options['user_id'] ?? null,
        ]);

        $actor = (array) ($plan['guard']['actor'] ?? []);

        $plan['audit'] = app(MutationAuditService::class)->previewFromPlan($plan, [
            'surface' => (string) ($options['surface'] ?? 'service'),
            'domain' => 'links',
            'run_id' => $options['run_id'] ?? null,
            'actor_type' => $actor['type'] ?? ($options['actor_type'] ?? null),
            'actor_id' => $actor['id'] ?? ($options['actor_id'] ?? null),
        ]);

        return $plan;
    }

    /**
     * Return validation errors for the planned mutation.
     *
     * @param string $operation
     * @param array $attributes
     * @return array
     */
    protected function validateMutation(string $operation, array $attributes): array
    {
        $errors = [];

        if ($operation === 'create') {
            foreach (['name', 'url'] as $field) {
                if (!$this->hasValue($attributes[$field] ?? null)) {
                    $errors[$field][] = 'required';
                }
            }
        }

        if ($operation === 'update') {
            foreach (['status', 'slug', 'name', 'url'] as $field) {
                if (array_key_exists($field, $attributes) && !$this->hasValue($attributes[$field])) {
                    $errors[$field][] = 'required';
                }
            }

            foreach (['is_pinned', 'is_recommended'] as $field) {
                if (array_key_exists($field, $attributes) && !is_bool($attributes[$field])) {
                    $errors[$field][] = 'invalid';
                }
            }
        }

        if (array_key_exists('status', $attributes) && !$this->validLinkStatus($attributes['status'])) {
            $errors['status'][] = 'invalid';
        }

        return $errors;
    }

    /**
     * Return validation errors for a Link bulk update payload.
     *
     * @param  array  $items
     * @return array
     */
    protected function bulkUpdateValidationErrors(array $items): array
    {
        $errors = [];

        if (!array_is_list($items) || empty($items)) {
            $errors['items'][] = 'required';

            return $errors;
        }

        if (count($items) > 100) {
            $errors['items'][] = 'maximum:100';

            return $errors;
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $errors['items'][] = "invalid:{$index}";
                continue;
            }

            $unknownFields = array_diff(array_keys($item), ['identifier', 'url', 'sort']);
            if (!empty($unknownFields)) {
                $errors['items'][] = "unsupported:{$index}";
            }

            if (!$this->hasValue($item['identifier'] ?? null)) {
                $errors['identifier'][] = "required:{$index}";
            } elseif (!is_string($item['identifier']) && !is_int($item['identifier'])) {
                $errors['identifier'][] = "invalid:{$index}";
            }

            if (!array_key_exists('url', $item) && !array_key_exists('sort', $item)) {
                $errors['items'][] = "patch_required:{$index}";
            }

            if (array_key_exists('url', $item) && (!is_string($item['url']) || trim($item['url']) === '')) {
                $errors['url'][] = "required:{$index}";
            }

            if (array_key_exists('sort', $item) && !$this->validBulkSort($item['sort'])) {
                $errors['sort'][] = "invalid:{$index}";
            }
        }

        return $errors;
    }

    /**
     * Determine whether a bulk sort value is an integer or integer-form string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function validBulkSort(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }

    /**
     * Build metadata for mutation command/service results.
     *
     * @param string $action
     * @param array $input
     * @param array $options
     * @param bool $dryRun
     * @return array
     */
    protected function mutationResultMeta(string $action, array $input, array $options, bool $dryRun): array
    {
        return [
            'surface' => (string) ($options['surface'] ?? 'service'),
            'command' => $options['command'] ?? null,
            'domain' => 'links',
            'action' => $action,
            'dry_run' => $dryRun,
            'force' => (bool) ($options['force'] ?? false),
            'website_id' => $this->hasValue($options['website_id'] ?? null) ? (int) $options['website_id'] : null,
            'actor_user_id' => $this->hasValue($options['actor_user_id'] ?? null) ? (int) $options['actor_user_id'] : null,
        ];
    }

    /**
     * Build a request payload for existing store hooks.
     *
     * @param array $input
     * @param array $plan
     * @return array
     */
    protected function requestPayloadFromPlan(array $input, array $plan): array
    {
        return array_merge($input, (array) ($plan['attributes'] ?? []), [
            'website_id' => (array) ($plan['relationships']['website_ids'] ?? []),
            'link_categories' => json_encode(array_map(fn(string $name) => ['name' => $name], (array) ($plan['relationships']['tags']['link_categories'] ?? []))),
            'link_tags' => json_encode(array_map(fn(string $name) => ['name' => $name], (array) ($plan['relationships']['tags']['link_tags'] ?? []))),
        ]);
    }

    /**
     * Run existing Link store hook validation rules.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function validateStoreHooks(Request $request): array
    {
        $rules = [];
        $messages = [];
        Event::dispatch('wncms.backend.links.store.before', [$request, &$rules, &$messages]);

        if (empty($rules)) {
            return [];
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }

    /**
     * Run existing Link update hook validation rules.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $link
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function validateUpdateHooks(Model $link, Request $request): array
    {
        $rules = [];
        $messages = [];
        Event::dispatch('wncms.backend.links.update.before', [$link, $request, &$rules, &$messages]);

        if (empty($rules)) {
            return [];
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }

    /**
     * Sync requested websites for a link mutation.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @param array $websiteIds
     * @return void
     */
    protected function syncMutationWebsites(Model $link, array $websiteIds): void
    {
        $websiteIds = array_values(array_unique(array_filter(array_map('intval', $websiteIds))));

        if (!empty($websiteIds) && method_exists($link, 'bindWebsites')) {
            $link->bindWebsites($websiteIds);
        }
    }

    /**
     * Sync requested tags for a link mutation.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @param array $tags
     * @return void
     */
    protected function syncMutationTags(Model $link, array $tags): void
    {
        if (!empty($tags['link_categories']) && method_exists($link, 'syncTagsWithType')) {
            $link->syncTagsWithType((array) $tags['link_categories'], 'link_category');
        }

        if (!empty($tags['link_tags']) && method_exists($link, 'syncTagsWithType')) {
            $link->syncTagsWithType((array) $tags['link_tags'], 'link_tag');
        }
    }

    /**
     * Calculate patch-style attribute changes.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @param array $attributes
     * @return array
     */
    protected function attributeChanges(Model $link, array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            $current = $this->currentMutationValue($link, $field);
            if ($current === $value) {
                continue;
            }

            $changes[$field] = [
                'from' => $current,
                'to' => $value,
            ];
        }

        return $changes;
    }

    /**
     * Determine whether a fresh update plan still matches its approved plan.
     *
     * @param  array  $approvedPlan
     * @param  array  $freshPlan
     * @return bool
     */
    protected function sameUpdateApproval(array $approvedPlan, array $freshPlan): bool
    {
        return ($approvedPlan['target']['id'] ?? null) === ($freshPlan['target']['id'] ?? null)
            && (array) ($approvedPlan['changes'] ?? []) === (array) ($freshPlan['changes'] ?? [])
            && (array) ($approvedPlan['relationships']['website_ids'] ?? []) === (array) ($freshPlan['relationships']['website_ids'] ?? []);
    }

    /**
     * Return a comparable current attribute value.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @param string $field
     * @return mixed
     */
    protected function currentMutationValue(Model $link, string $field): mixed
    {
        $value = $link->getAttribute($field);

        return match ($this->mutationAttributeTypes()[$field] ?? 'string') {
            'bool' => (bool) $value,
            'int' => $this->mutationInteger($value),
            'value' => $this->mutationValue($value),
            default => in_array($field, ['expired_at', 'hit_at'], true) ? $this->dateString($value) : $this->mutationString($value),
        };
    }

    /**
     * Return supported Link mutation attributes and value types.
     *
     * @return array
     */
    protected function mutationAttributeTypes(): array
    {
        return [
            'status' => 'string',
            'tracking_code' => 'string',
            'slug' => 'string',
            'name' => 'value',
            'url' => 'string',
            'slogan' => 'value',
            'description' => 'value',
            'external_thumbnail' => 'string',
            'remark' => 'string',
            'sort' => 'int',
            'color' => 'string',
            'background' => 'string',
            'is_pinned' => 'bool',
            'is_recommended' => 'bool',
            'expired_at' => 'string',
            'hit_at' => 'string',
            'clicks' => 'int',
            'contact' => 'string',
        ];
    }

    /**
     * Return the permission required for a mutation operation.
     *
     * @param string $operation
     * @return string
     */
    protected function mutationPermission(string $operation): string
    {
        return match ($operation) {
            'create' => 'link_create',
            'update' => 'link_edit',
            'delete' => 'link_delete',
            default => 'link_edit',
        };
    }

    /**
     * Return existing hook names related to a planned mutation.
     *
     * @param string $operation
     * @return array
     */
    protected function mutationHooks(string $operation): array
    {
        return match ($operation) {
            'create' => [
                'wncms.backend.links.store.before',
                'wncms.backend.links.store.attributes.before',
                'wncms.backend.links.store.after',
            ],
            'update' => [
                'wncms.backend.links.update.before',
                'wncms.backend.links.update.attributes.before',
                'wncms.backend.links.update.after',
            ],
            default => [],
        };
    }

    /**
     * Resolve website IDs requested for a mutation plan.
     *
     * @param array $input
     * @param array $options
     * @return array
     */
    protected function mutationWebsiteIds(array $input, array $options): array
    {
        $values = [];

        foreach (['website_ids', 'websites'] as $key) {
            if (array_key_exists($key, $input)) {
                $values = array_merge($values, $this->normalizeIdList($input[$key]));
            }
        }

        foreach (['website_id', 'website'] as $key) {
            if (array_key_exists($key, $input)) {
                $values = array_merge($values, $this->normalizeIdList($input[$key]));
            }
            if (array_key_exists($key, $options)) {
                $values = array_merge($values, $this->normalizeIdList($options[$key]));
            }
        }

        if (array_key_exists('website_ids', $options)) {
            $values = array_merge($values, $this->normalizeIdList($options['website_ids']));
        }

        return array_values(array_unique(array_filter($values, fn(int $id) => $id > 0)));
    }

    /**
     * Validate explicit website filters before they are used for target lookup.
     *
     * @param  array  $options
     * @return array
     */
    protected function lookupWebsiteErrors(array $options): array
    {
        $websiteIds = [];
        $hasExplicitWebsite = false;
        foreach (['website_id', 'website'] as $key) {
            if (array_key_exists($key, $options)) {
                $hasExplicitWebsite = $hasExplicitWebsite || $this->hasValue($options[$key]);
                $websiteIds = array_merge($websiteIds, $this->normalizeIdList($options[$key]));
            }
        }

        $websiteIds = array_values(array_unique(array_filter($websiteIds, fn(int $id) => $id > 0)));
        if (empty($websiteIds)) {
            return $hasExplicitWebsite ? ['website_ids' => ['invalid']] : [];
        }

        $websiteClass = wncms()->getModelClass('website');
        $existingIds = $websiteClass::query()->whereKey($websiteIds)->pluck((new $websiteClass())->getKeyName())
            ->map(fn($id) => (int) $id)
            ->all();
        $missingIds = array_values(array_diff($websiteIds, $existingIds));

        return empty($missingIds) ? [] : ['website_ids' => $missingIds];
    }

    /**
     * Normalize tag relationship input for a mutation plan.
     *
     * @param array $input
     * @return array
     */
    protected function mutationTags(array $input): array
    {
        return [
            'link_categories' => $this->normalizeNameList($input['link_categories'] ?? []),
            'link_tags' => $this->normalizeNameList($input['link_tags'] ?? []),
        ];
    }

    /**
     * Normalize media relationship input for a mutation plan.
     *
     * @param array $input
     * @return array
     */
    protected function mutationMedia(array $input): array
    {
        $media = [];

        foreach (['link_thumbnail', 'link_thumbnail_remove', 'link_icon', 'link_icon_remove'] as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $media[$key] = [
                'requested' => $this->mutationValue($input[$key]),
                'status' => 'unsupported_in_dry_run_v1',
            ];
        }

        return $media;
    }

    /**
     * Normalize ID list input.
     *
     * @param mixed $value
     * @return array
     */
    protected function normalizeIdList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        return Collection::make((array) $value)
            ->map(function ($id) {
                if (is_array($id)) {
                    return (int) ($id['id'] ?? 0);
                }

                return (int) $id;
            })
            ->values()
            ->all();
    }

    /**
     * Normalize tag name list input.
     *
     * @param mixed $value
     * @return array
     */
    protected function normalizeNameList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $value)));
        }

        return Collection::make((array) $value)
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['name'] ?? $item['value'] ?? $item['id'] ?? null;
                }

                return $item;
            })
            ->filter(fn($item) => $this->hasValue($item))
            ->map(fn($item) => (string) $item)
            ->values()
            ->all();
    }

    /**
     * Generate or reuse a dry-run UID.
     *
     * @param array $options
     * @return string
     */
    protected function generatedLinkUid(array $options): string
    {
        if ($this->hasValue($options['uid'] ?? null)) {
            return (string) $options['uid'];
        }

        return wncms()->getUniqueSlug('links', 'slug', 8, 'lower');
    }

    /**
     * Check whether a status is valid for Link.
     *
     * @param mixed $status
     * @return bool
     */
    protected function validLinkStatus(mixed $status): bool
    {
        $modelClass = wncms()->getModelClass('link');
        $statuses = defined("{$modelClass}::STATUSES") ? $modelClass::STATUSES : [];

        return in_array((string) $status, $statuses, true);
    }

    /**
     * Normalize mutation strings.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function mutationString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value)) {
            return json_encode($this->scalar($value), JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    /**
     * Normalize mutation integers.
     *
     * @param mixed $value
     * @return int|null
     */
    protected function mutationInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Normalize mutation booleans.
     *
     * @param mixed $value
     * @return bool
     */
    protected function mutationBoolean(mixed $value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * Determine whether a patch value is an accepted boolean literal.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function validMutationBoolean(mixed $value): bool
    {
        if (is_bool($value) || $value === 0 || $value === 1) {
            return true;
        }

        return is_string($value) && in_array(strtolower($value), [
            '0', '1', 'true', 'false', 'yes', 'no', 'on', 'off',
        ], true);
    }

    /**
     * Normalize mutation values that can be scalar or translated arrays.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function mutationValue(mixed $value): mixed
    {
        return $this->scalar($value);
    }

    /**
     * Normalize nullable patch values while preserving explicit clears.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutationNullableValue(mixed $value): mixed
    {
        return $this->hasValue($value) ? $this->mutationValue($value) : null;
    }

    /**
     * Normalize a link collection or paginator into list item payloads.
     *
     * @param mixed $links
     * @return array
     */
    protected function normalizeListItems(mixed $links): array
    {
        $collection = $links instanceof LengthAwarePaginator
            ? $links->getCollection()
            : Collection::make($links);

        return $collection
            ->map(fn(Model $link) => $this->normalizeLink($link, false))
            ->values()
            ->all();
    }

    /**
     * Normalize one link model.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @param bool $includeDetails
     * @return array
     */
    protected function normalizeLink(Model $link, bool $includeDetails): array
    {
        $payload = [
            'id' => (int) $link->getKey(),
            'status' => $this->scalar($link->getAttribute('status')),
            'slug' => $this->scalar($link->getAttribute('slug')),
            'name' => $this->scalar($link->getAttribute('name')),
            'url' => $this->scalar($link->getAttribute('url')),
            'sort' => $link->getAttribute('sort') === null ? null : (int) $link->getAttribute('sort'),
            'clicks' => $link->getAttribute('clicks') === null ? null : (int) $link->getAttribute('clicks'),
            'is_pinned' => (bool) $link->getAttribute('is_pinned'),
            'is_recommended' => (bool) $link->getAttribute('is_recommended'),
            'website_ids' => $this->websiteIds($link),
            'created_at' => $this->dateString($link->getAttribute('created_at')),
            'updated_at' => $this->dateString($link->getAttribute('updated_at')),
        ];

        if (!$includeDetails) {
            return $payload;
        }

        return array_merge($payload, [
            'tracking_code' => $this->scalar($link->getAttribute('tracking_code')),
            'slogan' => $this->scalar($link->getAttribute('slogan')),
            'description' => $this->scalar($link->getAttribute('description')),
            'external_thumbnail' => $this->scalar($link->getAttribute('external_thumbnail')),
            'remark' => $this->scalar($link->getAttribute('remark')),
            'color' => $this->scalar($link->getAttribute('color')),
            'background' => $this->scalar($link->getAttribute('background')),
            'contact' => $this->scalar($link->getAttribute('contact')),
            'expired_at' => $this->dateString($link->getAttribute('expired_at')),
            'hit_at' => $this->dateString($link->getAttribute('hit_at')),
            'websites' => $this->websites($link),
        ]);
    }

    /**
     * Build pagination metadata for a list result.
     *
     * @param mixed $links
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function paginationMeta(mixed $links, int $page, int $perPage): array
    {
        if ($links instanceof LengthAwarePaginator) {
            return [
                'page' => $links->currentPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
                'last_page' => $links->lastPage(),
            ];
        }

        $count = Collection::make($links)->count();

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $count,
            'last_page' => $count > 0 ? 1 : 0,
        ];
    }

    /**
     * Return related website IDs when the relation is loaded.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @return array
     */
    protected function websiteIds(Model $link): array
    {
        if (!$link->relationLoaded('websites')) {
            return [];
        }

        return $link->getRelation('websites')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Return related website summaries when the relation is loaded.
     *
     * @param \Illuminate\Database\Eloquent\Model $link
     * @return array
     */
    protected function websites(Model $link): array
    {
        if (!$link->relationLoaded('websites')) {
            return [];
        }

        return $link->getRelation('websites')
            ->map(fn(Model $website) => [
                'id' => (int) $website->getKey(),
                'domain' => $this->scalar($website->getAttribute('domain')),
                'site_name' => $this->scalar($website->getAttribute('site_name')),
            ])
            ->values()
            ->all();
    }

    /**
     * Normalize the status option.
     *
     * @param mixed $status
     * @return string|null
     */
    protected function normalizeStatus(mixed $status): ?string
    {
        $status = trim((string) $status);

        return $status === '' || strtolower($status) === 'all' ? null : $status;
    }

    /**
     * Check whether an option value should be applied.
     *
     * @param mixed $value
     * @return bool
     */
    protected function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Convert mixed scalar-ish values into JSON-safe output.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function scalar(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return json_decode(json_encode($value), true);
    }

    /**
     * Format date values for automation output.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function dateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return (string) $value;
    }
}
