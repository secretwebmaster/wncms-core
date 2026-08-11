<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Filesystem as MediaFilesystem;
use Throwable;
use Wncms\Services\Automation\BackendMutationAuditService;

class LinkController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        Event::dispatch('wncms.backend.links.index.query.before', [$request, &$q]);
        $this->applyBackendListWebsiteScope($q);

        if ($request->input('sort') === 'views_yesterday') {
            $yesterday = now()->subDay()->toDateString();
            $q->leftJoin('total_views as tv_y', function ($join) use ($yesterday) {
                $join->on('links.id', '=', 'tv_y.link_id')
                    ->where('tv_y.date', $yesterday);
            });
            $q->orderByDesc('tv_y.total');
        }

        if ($request->input('keyword')) {
            $q->where(function ($subq) use ($request) {
                $keyword = str_replace('@', '', $request->input('keyword'));
                $keyword = str_replace('https://t.me/', '', $keyword);
                $subq->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('url', 'like', '%' . $keyword . '%')
                    ->orWhere('remark', 'like', '%' . $keyword . '%')
                    ->orWhere('contact', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->link_category_id) {
            $q->whereHas('tags', function ($q) use ($request) {
                $q->where('type', 'link_category')->where(function ($q) use ($request) {
                    $q->where('tags.id', $request->link_category_id)->orWhere('tags.name', $request->link_category_id);
                });
            });
        }

        if ($request->status) {
            $q->where('status', $request->status);
        }

        // $q->orderBy('is_pinned', 'desc');
        // $q->orderBy('sort', 'desc');
        $q->orderBy('links.id', 'desc');

        $links = $q->paginate($request->page_size ?? 100);

        $parentLinkCategories = wncms()->getModelClass('tag')::where('type', 'link_category')->whereNull('parent_id')->get()->unique();

        return $this->view('backend.links.index', [
            'page_title' =>  wncms()->getModelWord('link', 'management'),
            'links' => $links,
            'statuses' => $this->modelClass::STATUSES,
            'parentLinkCategories' => $parentLinkCategories,
            'clickModel' => null,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $link = $this->modelClass::find($id);
            if (!$link) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.link')]));
            }
        } else {
            $link = new $this->modelClass;
        }

        $view = 'backend.links.create';
        $params = [
            'page_title' =>  wncms()->getModelWord('link', 'management'),
            'link' => $link,
            'statuses' => $this->modelClass::STATUSES,
        ];

        Event::dispatch('wncms.backend.links.create.resolve', [&$view, &$params]);

        return $this->view($view, $params);
    }

    public function store(Request $request)
    {
        $rules = [];
        $messages = [];
        Event::dispatch('wncms.backend.links.store.before', [$request, &$rules, &$messages]);

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        $uid = wncms()->getUniqueSlug('links', 'slug', 8, 'lower');

        $attributes = [
            'status' => $request->input('status'),
            'tracking_code' => $request->input('tracking_code') ?: $uid,
            'slug' => $request->input('slug') ?: $uid,
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'slogan' => $request->input('slogan'),
            'description' => $request->input('description'),
            'external_thumbnail' => $request->input('external_thumbnail'),
            'remark' => $request->input('remark'),
            'sort' => $request->input('sort'),
            'color' => $request->input('color'),
            'background' => $request->input('background'),
            'is_pinned' => $request->input('is_pinned') ? true : false,
            'is_recommended' => $request->input('is_recommended') ? true : false,
            'expired_at' => $request->input('expired_at'),
            'hit_at' => $request->input('hit_at'),
            'clicks' => $request->input('clicks'),
            'contact' => $request->input('contact'),
        ];

        Event::dispatch('wncms.backend.links.store.attributes.before', [$request, &$attributes]);

        $addedMedia = [];
        $removedMedia = [];

        try {
            $link = $this->auditedMutation(function () use ($attributes, $request, &$addedMedia, &$removedMedia) {
                $link = $this->modelClass::create($attributes);
                $this->syncBackendMutationWebsites($link);

                $this->mutateLinkMedia($link, $request, $addedMedia, $removedMedia);

                //tags
                $link->syncTagsFromTagify($request->link_categories, 'link_category');
                $link->syncTagsFromTagify($request->link_tags, 'link_tag');

                Event::dispatch('wncms.backend.links.store.after', [$link, $request]);

                if ($this->mutationAuditService()->enabled()) {
                    $link->refresh();
                    $after = $this->linkAuditState($link);
                    $this->mutationAuditService()->write(
                        $link,
                        'links',
                        'create',
                        'link_create',
                        [],
                        $after,
                        (array) ($after['relationships']['website_ids'] ?? []),
                        (array) ($after['relationships'] ?? []),
                        null,
                        'Link created.'
                    );
                }

                return $link;
            });
        } catch (Throwable $exception) {
            $this->removeMediaFiles($addedMedia);
            throw $exception;
        }

        $this->removeMediaFiles($removedMedia);

        $this->flush(['links']);

        return redirect()->route('links.edit', [
            'id' => $link->id,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $link = $this->modelClass::find($id);
        if (!$link) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.link')]));
        }

        $view = 'backend.links.edit';
        $params = [
            'page_title' => wncms()->getModelWord('link', 'management'),
            'link' => $link,
            'statuses' => $this->modelClass::STATUSES,
        ];

        Event::dispatch('wncms.backend.links.edit.resolve', [&$view, &$params]);

        return $this->view($view, $params);
    }

    public function update(Request $request, $id)
    {
        $link = $this->modelClass::find($id);
        if (!$link) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.link')]));
        }

        $rules = [];
        $messages = [];
        Event::dispatch('wncms.backend.links.update.before', [$link, $request, &$rules, &$messages]);

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        $attributes = [
            'status' => $request->input('status'),
            'tracking_code' => $request->input('tracking_code') ?? $link->tracking_code,
            'slug' => $request->input('slug') ?: $link->slug,
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'slogan' => $request->input('slogan'),
            'description' => $request->input('description'),
            'external_thumbnail' => $request->input('external_thumbnail'),
            'remark' => $request->input('remark'),
            'sort' => $request->input('sort'),
            'color' => $request->input('color'),
            'background' => $request->input('background'),
            'is_pinned' => (bool) $request->input('is_pinned'),
            'is_recommended' => (bool) $request->input('is_recommended'),
            'expired_at' => $request->input('expired_at'),
            'hit_at' => $request->input('hit_at'),
            'clicks' => $request->input('clicks'),
            'contact' => $request->input('contact'),
        ];

        Event::dispatch('wncms.backend.links.update.attributes.before', [$link, $request, &$attributes]);

        $addedMedia = [];
        $removedMedia = [];

        try {
            $this->auditedMutation(function () use ($attributes, $link, $request, &$addedMedia, &$removedMedia): void {
                $auditEnabled = $this->mutationAuditService()->enabled();
                if ($auditEnabled) {
                    $link = $this->modelClass::query()->whereKey($link->getKey())->lockForUpdate()->firstOrFail();
                }
                $before = $auditEnabled ? $this->linkAuditState($link) : [];

                if ($link->update($attributes) !== true) {
                    throw new RuntimeException('Link update was cancelled.');
                }
                $this->syncBackendMutationWebsites($link);

                $this->mutateLinkMedia($link, $request, $addedMedia, $removedMedia);

                // tags
                if (method_exists($link, 'syncTagsFromTagify')) {
                    $link->syncTagsFromTagify($request->link_categories, 'link_category');
                    $link->syncTagsFromTagify($request->link_tags, 'link_tag');
                }

                Event::dispatch('wncms.backend.links.update.after', [$link, $request]);

                if ($auditEnabled) {
                    $link->refresh();
                    $after = $this->linkAuditState($link);
                    if ($before !== $after) {
                        $this->mutationAuditService()->write(
                            $link,
                            'links',
                            'update',
                            'link_edit',
                            $before,
                            $after,
                            (array) ($after['relationships']['website_ids'] ?? []),
                            [
                                'before' => (array) ($before['relationships'] ?? []),
                                'after' => (array) ($after['relationships'] ?? []),
                            ],
                            null,
                            'Link updated.'
                        );
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->removeMediaFiles($addedMedia);
            throw $exception;
        }

        $this->removeMediaFiles($removedMedia);

        $this->flush(['links']);

        return redirect()->route('links.edit', ['id' => $link->id])
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Delete one Link and audit its pre-delete state when enabled.
     *
     * @param  int|string  $id
     * @return mixed
     */
    public function destroy($id)
    {
        if (!$this->mutationAuditService()->enabled()) {
            return parent::destroy($id);
        }

        $link = $this->modelClass::find($id);
        if (!$link) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular),
            ]));
        }

        $this->auditedMutation(function () use ($link): void {
            $link = $this->modelClass::query()->whereKey($link->getKey())->lockForUpdate()->first();
            if (!$link) {
                throw new RuntimeException('Link delete target became unavailable.');
            }

            $before = $this->linkAuditState($link);
            if ($link->delete() !== true) {
                throw new RuntimeException('Link delete was cancelled.');
            }
            $this->mutationAuditService()->write(
                $link,
                'links',
                'delete',
                'link_delete',
                $before,
                [],
                (array) ($before['relationships']['website_ids'] ?? []),
                (array) ($before['relationships'] ?? []),
                null,
                'Link deleted.'
            );
        });

        $this->flush(['links']);

        return back()->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Delete multiple Links and audit each successful deletion when enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function bulk_delete(Request $request)
    {
        if (!$this->mutationAuditService()->enabled()) {
            return parent::bulk_delete($request);
        }

        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(',', (string) $request->model_ids);
        $runId = (string) Str::uuid();
        $count = DB::transaction(function () use ($modelIds, $runId): int {
            $deleted = 0;
            $links = $this->modelClass::query()
                ->whereIn('id', $modelIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($links as $link) {
                $before = $this->linkAuditState($link);
                if ($this->modelClass::query()->whereKey($link->getKey())->delete() !== 1) {
                    continue;
                }

                $this->mutationAuditService()->write(
                    $link,
                    'links',
                    'bulk_delete',
                    'link_bulk_delete',
                    $before,
                    [],
                    (array) ($before['relationships']['website_ids'] ?? []),
                    (array) ($before['relationships'] ?? []),
                    $runId,
                    'Link bulk deleted.'
                );
                $deleted++;
            }

            return $deleted;
        });

        if ($count > 0) {
            $this->flush(['links']);
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return back()->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }

    /**
     * Bulk update link sort and url
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulk_update(Request $request)
    {
        if ($this->mutationAuditService()->enabled()) {
            return $this->auditedBulkUpdate($request);
        }

        $count = 0;

        foreach ($request->data as $item) {
            $link = $this->modelClass::find($item['id']);

            if (!$link) {
                continue;
            }

            $updateData = [];

            // update sort if changed
            if (isset($item['sort']) && $link->sort != $item['sort']) {
                $updateData['sort'] = $item['sort'];
            }

            // update url if changed
            if (isset($item['url']) && $link->url != $item['url']) {
                $updateData['url'] = $item['url'];
            }

            // skip only when both unchanged
            if (empty($updateData)) {
                continue;
            }

            // perform update
            $link->update($updateData);
            $count++;
        }

        if ($count > 0) {
            wncms()->cache()->flush(['links']);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated_count', ['count' => $count]),
            'data' => ['count' => $count]
        ]);
    }

    public function bulk_sync_tags(Request $request)
    {
        try {
            \Log::info($request->all());
            parse_str($request->formData, $formDataArray);
            // info($formDataArray);

            if (empty($request->model_ids)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.model_ids_are_not_found'),
                    'restoreBtn' => true,
                ]);
            }

            //receive checked ids
            $links = $this->modelClass::whereIn('id', $request->model_ids)->get();
            if ($links->isEmpty()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.link_is_not_fount'),
                    'restoreBtn' => true,
                ]);
            }

            //get action
            if (empty($formDataArray['action']) || !in_array($formDataArray['action'], ['sync', 'attach', 'detach'])) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.action_is_not_found'),
                    'restoreBtn' => true,
                ]);
            }

            $link_categories = collect(json_decode($formDataArray['link_categories'], true))->pluck('name')->toArray();
            // info($link_categories);

            $link_tags = collect(json_decode($formDataArray['link_tags'], true))->pluck('name')->toArray();
            // info($link_tags);

            $action = (string) $formDataArray['action'];
            $changed = 0;

            if ($this->mutationAuditService()->enabled()) {
                $runId = (string) Str::uuid();
                $modelIds = $links->pluck('id')->all();
                $changed = DB::transaction(function () use ($modelIds, $action, $link_categories, $link_tags, $runId): int {
                    $count = 0;
                    $links = $this->modelClass::query()
                        ->whereIn('id', $modelIds)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($links as $link) {
                        $before = $this->linkAuditState($link);
                        $this->applyBulkTagMutation($link, $action, $link_categories, $link_tags);
                        $after = $this->linkAuditState($link);
                        if ($before === $after) {
                            continue;
                        }

                        $this->mutationAuditService()->write(
                            $link,
                            'links',
                            'bulk_sync_tags',
                            'link_edit',
                            $before,
                            $after,
                            (array) ($after['relationships']['website_ids'] ?? []),
                            [
                                'before' => (array) ($before['relationships'] ?? []),
                                'after' => (array) ($after['relationships'] ?? []),
                            ],
                            $runId,
                            'Link bulk tags synchronized.'
                        );
                        $count++;
                    }

                    return $count;
                });
            } else {
                foreach ($links as $link) {
                    $this->applyBulkTagMutation($link, $action, $link_categories, $link_tags);
                }

                $changed = $links->count();
            }

            if ($changed > 0) {
                wncms()->cache()->flush(['links']);
            }

            return response()->json([
                'status' => 'success',
                'title' => __('wncms::word.success'),
                'message' => __('wncms::word.successfully_updated_all'),
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json([
                'status' => 'fail',
                'title' => __('wncms::word.failed'),
                'message' => __('wncms::word.error') . ": " . $e->getMessage(),
                'restoreBtn' => true,
            ]);
        }
    }

    /**
     * Run an audited bulk Link update in one transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function auditedBulkUpdate(Request $request)
    {
        $runId = (string) Str::uuid();
        $count = DB::transaction(function () use ($request, $runId): int {
            $changed = 0;
            $items = (array) $request->data;
            $modelIds = collect($items)
                ->pluck('id')
                ->filter(fn ($id): bool => is_numeric($id))
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();
            $links = $this->modelClass::query()
                ->whereIn('id', $modelIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($link): int => (int) $link->getKey());

            foreach ($items as $item) {
                $link = $links->get((int) $item['id']);
                if (!$link) {
                    continue;
                }

                $updateData = [];
                if (isset($item['sort']) && $link->sort != $item['sort']) {
                    $updateData['sort'] = $item['sort'];
                }
                if (isset($item['url']) && $link->url != $item['url']) {
                    $updateData['url'] = $item['url'];
                }
                if (empty($updateData)) {
                    continue;
                }

                $before = $this->linkAuditState($link);
                if ($link->update($updateData) !== true) {
                    throw new RuntimeException('Link bulk update was cancelled.');
                }
                $link->refresh();
                $after = $this->linkAuditState($link);
                if ($before === $after) {
                    continue;
                }
                $this->mutationAuditService()->write(
                    $link,
                    'links',
                    'bulk_update',
                    'link_edit',
                    $before,
                    $after,
                    (array) ($after['relationships']['website_ids'] ?? []),
                    [
                        'before' => (array) ($before['relationships'] ?? []),
                        'after' => (array) ($after['relationships'] ?? []),
                    ],
                    $runId,
                    'Link bulk updated.'
                );
                $changed++;
            }

            return $changed;
        });

        if ($count > 0) {
            wncms()->cache()->flush(['links']);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated_count', ['count' => $count]),
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Apply one existing bulk tag operation to a Link.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $link
     * @param  string  $action
     * @param  array  $linkCategories
     * @param  array  $linkTags
     * @return void
     */
    protected function applyBulkTagMutation($link, string $action, array $linkCategories, array $linkTags): void
    {
        if ($action === 'sync') {
            if (!empty($linkCategories)) {
                $link->syncTagsWithType($linkCategories, 'link_category');
            }
            if (!empty($linkTags)) {
                $link->syncTagsWithType($linkTags, 'link_tag');
            }
        }

        if ($action === 'attach') {
            if (!empty($linkCategories)) {
                $link->attachTags($linkCategories, 'link_category');
            }
            if (!empty($linkTags)) {
                $link->attachTags($linkTags, 'link_tag');
            }
        }

        if ($action === 'detach') {
            if (!empty($linkCategories)) {
                $link->detachTags($linkCategories, 'link_category');
            }
            if (!empty($linkTags)) {
                $link->detachTags($linkTags, 'link_tag');
            }
        }
    }

    /**
     * Resolve the backend mutation audit adapter.
     *
     * @return \Wncms\Services\Automation\BackendMutationAuditService
     */
    protected function mutationAuditService(): BackendMutationAuditService
    {
        return app(BackendMutationAuditService::class);
    }

    /**
     * Apply Link media changes with filesystem compensation for audited rollbacks.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $link
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $addedMedia
     * @param  array  $removedMedia
     * @return void
     */
    protected function mutateLinkMedia($link, Request $request, array &$addedMedia, array &$removedMedia): void
    {
        $collections = [
            'link_thumbnail' => 'link_thumbnail_remove',
            'link_icon' => 'link_icon_remove',
        ];

        foreach ($collections as $collection => $removeInput) {
            $shouldRemove = !empty($request->{$removeInput});
            $shouldUpload = !empty($request->{$collection});
            if (!$shouldRemove && !$shouldUpload) {
                continue;
            }

            if (!$this->mutationAuditService()->enabled()) {
                if ($shouldRemove) {
                    $link->clearMediaCollection($collection);
                }
                if ($shouldUpload) {
                    $link->addMediaFromRequest($collection)->toMediaCollection($collection);
                }
                continue;
            }

            $beforeMedia = $link->media()->where('collection_name', $collection)->get();
            $mediaClass = $link->getMediaModel();
            $newMedia = $mediaClass::withoutEvents(function () use ($collection, $link, $shouldRemove, $shouldUpload) {
                if ($shouldRemove) {
                    $link->clearMediaCollection($collection);
                }

                return $shouldUpload
                    ? $link->addMediaFromRequest($collection)->toMediaCollection($collection)
                    : null;
            });

            if ($newMedia !== null) {
                $addedMedia[] = $newMedia;
            }

            $remainingIds = $link->media()
                ->where('collection_name', $collection)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($beforeMedia as $media) {
                if (!in_array((int) $media->getKey(), $remainingIds, true)) {
                    $removedMedia[] = $media;
                }
            }
        }
    }

    /**
     * Remove physical files for media rows already committed or rolled back.
     *
     * @param  array  $mediaItems
     * @return void
     */
    protected function removeMediaFiles(array $mediaItems): void
    {
        $filesystem = app(MediaFilesystem::class);
        foreach ($mediaItems as $media) {
            $filesystem->removeAllFiles($media);
        }
    }

    /**
     * Capture deterministic Link attributes and relationship state.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $link
     * @return array
     */
    protected function linkAuditState($link): array
    {
        $websiteIds = $link->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $link->unsetRelation('tags');
        $linkCategories = $link->tagsWithType('link_category')->pluck('name')->sort()->values()->all();
        $linkTags = $link->tagsWithType('link_tag')->pluck('name')->sort()->values()->all();
        $media = [];
        foreach (['link_thumbnail', 'link_icon'] as $collection) {
            $media[$collection] = $link->media()
                ->where('collection_name', $collection)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
        }

        return $this->mutationAuditService()->snapshot($link, [
            'website_ids' => $websiteIds,
            'link_categories' => $linkCategories,
            'link_tags' => $linkTags,
            'media' => $media,
        ]);
    }

    /**
     * Execute a mutation directly when disabled or transactionally when audited.
     *
     * @param  callable  $callback
     * @return mixed
     */
    protected function auditedMutation(callable $callback): mixed
    {
        if (!$this->mutationAuditService()->enabled()) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
