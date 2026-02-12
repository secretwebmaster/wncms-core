<?php

namespace Wncms\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteController extends ApiController
{
    public function index(Request $request)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_index')) return $resp;

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        $websiteModel = wncms()->getModelClass('website');
        $q = $user->hasRole('admin') ? $websiteModel::query() : $user->websites();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $q->where(function ($subq) use ($keyword) {
                $subq->where('domain', 'like', "%{$keyword}%")
                    ->orWhere('site_name', 'like', "%{$keyword}%");
            });
        }

        $q->with(['domain_aliases', 'translations', 'media']);
        $q->orderBy('id', 'desc');

        $pageSize = min(max((int) $request->input('page_size', 20), 1), 100);
        $websites = $q->paginate($pageSize);

        return $this->success(
            $websites->items(),
            'success',
            200,
            [
                'total' => $websites->total(),
                'count' => $websites->count(),
                'page_size' => $websites->perPage(),
                'current_page' => $websites->currentPage(),
                'last_page' => $websites->lastPage(),
                'has_more' => $websites->hasMorePages(),
                'next' => $websites->nextPageUrl(),
                'previous' => $websites->previousPageUrl(),
            ]
        );
    }

    public function store(Request $request)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_store')) return $resp;

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        if (!$user->hasRole('admin')) {
            return $this->fail(__('wncms::word.unauthorized_action'), 403);
        }

        $request->validate([
            'site_name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'remark' => 'nullable|string|max:255',
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));
        if (empty($domain)) {
            return $this->fail('Invalid domain format', 422);
        }

        if ($domainError = $this->ensureDomainAvailable($domain)) {
            return $domainError;
        }

        $websiteModel = wncms()->getModelClass('website');
        $website = $websiteModel::create([
            'site_name' => $request->input('site_name'),
            'domain' => $domain,
            'theme' => $request->input('theme'),
            'remark' => $request->input('remark'),
            'user_id' => $user->id,
        ]);

        $website->users()->syncWithoutDetaching([$user->id]);
        wncms()->cache()->tags(['websites'])->flush();

        return $this->success(
            $website->load(['domain_aliases', 'translations', 'media']),
            __('wncms::word.successfully_created')
        );
    }

    public function show(Request $request, $id)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_show')) return $resp;

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        $website = $this->findWebsiteForUser($user, (int) $id);
        if (!$website) {
            return $this->fail(__('wncms::word.website_is_not_found'), 404);
        }

        return $this->success($website, 'success');
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_update')) return $resp;

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        $website = $this->findWebsiteForUser($user, (int) $id);
        if (!$website) {
            return $this->fail(__('wncms::word.website_is_not_found'), 404);
        }

        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'domain' => 'nullable|string|max:255',
            'site_name' => 'nullable',
            'site_logo' => 'nullable|string|max:255',
            'site_favicon' => 'nullable|string|max:255',
            'site_slogan' => 'nullable',
            'site_seo_keywords' => 'nullable',
            'site_seo_description' => 'nullable',
            'theme' => 'nullable|string|max:100',
            'homepage' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:255',
            'meta_verification' => 'nullable|string',
            'head_code' => 'nullable|string',
            'body_code' => 'nullable|string',
            'analytics' => 'nullable|string',
            'license' => 'nullable|string|max:255',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
        ]);

        $websiteModel = wncms()->getModelClass('website');
        $normalizedTranslatableInputs = $this->getNormalizedTranslatableInputs($request, $websiteModel);
        $this->mergeTranslatableBaseValuesIntoRequest($request, $normalizedTranslatableInputs);

        $updates = [];
        $fields = [
            'user_id',
            'site_name',
            'site_logo',
            'site_favicon',
            'site_slogan',
            'site_seo_keywords',
            'site_seo_description',
            'theme',
            'homepage',
            'remark',
            'meta_verification',
            'head_code',
            'body_code',
            'analytics',
            'license',
        ];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updates[$field] = $request->input($field);
            }
        }

        foreach (['enabled_page_cache', 'enabled_data_cache'] as $boolField) {
            if ($request->has($boolField)) {
                $updates[$boolField] = $request->boolean($boolField);
            }
        }

        if ($request->has('domain')) {
            $newDomain = $this->normalizeDomain((string) $request->input('domain'));
            if (empty($newDomain)) {
                return $this->fail('Invalid domain format', 422);
            }

            if ($newDomain !== $website->domain) {
                $domainAliasModel = wncms()->getModelClass('domain_alias');
                $selfAlias = $domainAliasModel::where('website_id', $website->id)->where('domain', $newDomain)->first();

                if ($selfAlias) {
                    $selfAlias->delete();
                } elseif ($domainError = $this->ensureDomainAvailable($newDomain, $website->id)) {
                    return $domainError;
                }

                $updates['domain'] = $newDomain;
            }
        }

        if (!empty($updates)) {
            $website->update($updates);
            $this->applyModelTranslations($website, $normalizedTranslatableInputs);
            wncms()->cache()->tags(['websites'])->flush();
        }

        return $this->success(
            $website->fresh()->load(['domain_aliases', 'translations', 'media']),
            __('wncms::word.successfully_updated')
        );
    }

    public function delete(Request $request, $id)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_delete')) return $resp;

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        if (!$user->hasRole('admin')) {
            return $this->fail(__('wncms::word.unauthorized_action'), 403);
        }

        $websiteModel = wncms()->getModelClass('website');
        $website = $websiteModel::find((int) $id);
        if (!$website) {
            return $this->fail(__('wncms::word.website_is_not_found'), 404);
        }

        $website->delete();
        wncms()->cache()->tags(['websites'])->flush();

        return $this->success([], __('wncms::word.successfully_deleted'));
    }

    public function addDomain(Request $request)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_add_domain')) return $resp;

        $request->validate([
            'api_token' => 'required|string',
            'website_id' => 'required|integer',
            'domain' => 'required|string|max:255',
        ]);

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        $websiteId = (int) $request->input('website_id');
        $domain = $this->normalizeDomain($request->input('domain'));
        if (empty($domain)) {
            return $this->fail('Invalid domain format', 422);
        }

        $website = $this->findWebsiteForUser($user, $websiteId);

        if (!$website) {
            return $this->fail(__('wncms::word.website_is_not_found'), 404);
        }

        if ($website->domain === $domain) {
            return $this->success([
                'website_id' => $website->id,
                'domain' => $domain,
                'already_exists' => true,
                'is_primary_domain' => true,
            ], 'Domain is already the primary domain of this website');
        }

        $domainAliasModel = wncms()->getModelClass('domain_alias');
        $existingAlias = $domainAliasModel::where('website_id', $website->id)->where('domain', $domain)->first();

        if ($existingAlias) {
            return $this->success([
                'website_id' => $website->id,
                'domain' => $domain,
                'domain_alias_id' => $existingAlias->id,
                'already_exists' => true,
                'is_primary_domain' => false,
            ], 'Domain alias already exists for this website');
        }

        if ($domainError = $this->ensureDomainAvailable($domain, $website->id)) {
            return $domainError;
        }

        $domainAlias = $domainAliasModel::create([
            'website_id' => $website->id,
            'domain' => $domain,
        ]);

        wncms()->cache()->tags(['websites'])->flush();

        return $this->success([
            'website_id' => $website->id,
            'domain' => $domainAlias->domain,
            'domain_alias_id' => $domainAlias->id,
            'already_exists' => false,
            'is_primary_domain' => false,
        ], 'Domain alias created');
    }

    public function removeDomain(Request $request)
    {
        if ($resp = $this->guardWebsiteApi('wncms_api_website_remove_domain')) return $resp;

        $request->validate([
            'api_token' => 'required|string',
            'website_id' => 'required|integer',
            'domain' => 'required|string|max:255',
        ]);

        [$user, $authError] = $this->resolveApiUser($request);
        if ($authError) {
            return $authError;
        }

        $website = $this->findWebsiteForUser($user, (int) $request->input('website_id'));
        if (!$website) {
            return $this->fail(__('wncms::word.website_is_not_found'), 404);
        }

        $domain = $this->normalizeDomain((string) $request->input('domain'));
        if (empty($domain)) {
            return $this->fail('Invalid domain format', 422);
        }

        $aliasCount = $website->domain_aliases()->count();
        $domainCount = 1 + $aliasCount;
        if ($domainCount <= 1) {
            return $this->fail('Cannot remove the last domain of a website', 422);
        }

        if ($website->domain === $domain) {
            $replacementAlias = $website->domain_aliases()->orderBy('id')->first();
            if (!$replacementAlias) {
                return $this->fail('Cannot remove the last domain of a website', 422);
            }

            DB::transaction(function () use ($website, $replacementAlias) {
                $website->update(['domain' => $replacementAlias->domain]);
                $replacementAlias->delete();
            });

            wncms()->cache()->tags(['websites'])->flush();

            return $this->success([
                'website_id' => $website->id,
                'removed_domain' => $domain,
                'new_primary_domain' => $website->fresh()->domain,
            ], __('wncms::word.successfully_updated'));
        }

        $alias = $website->domain_aliases()->where('domain', $domain)->first();
        if (!$alias) {
            return $this->fail('Domain alias not found on this website', 404);
        }

        $alias->delete();
        wncms()->cache()->tags(['websites'])->flush();

        return $this->success([
            'website_id' => $website->id,
            'removed_domain' => $domain,
            'new_primary_domain' => $website->domain,
        ], __('wncms::word.successfully_deleted'));
    }

    protected function resolveApiUser(Request $request): array
    {
        $token = (string) $request->input('api_token', '');
        if (empty($token)) {
            return [null, $this->fail('Missing api_token', 401)];
        }

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::where('api_token', $token)->first();
        if (!$user) {
            return [null, $this->fail('Invalid api_token', 401)];
        }

        auth()->login($user);
        return [$user, null];
    }

    protected function findWebsiteForUser($user, int $websiteId)
    {
        if ($websiteId <= 0) {
            return null;
        }

        $q = $user->hasRole('admin')
            ? wncms()->getModelClass('website')::query()
            : $user->websites();

        return $q->with(['domain_aliases', 'translations', 'media'])
            ->where('websites.id', $websiteId)
            ->first();
    }

    protected function ensureDomainAvailable(string $domain, ?int $exceptWebsiteId = null)
    {
        $websiteModel = wncms()->getModelClass('website');
        $domainAliasModel = wncms()->getModelClass('domain_alias');

        $websiteQuery = $websiteModel::where('domain', $domain);
        if (!is_null($exceptWebsiteId)) {
            $websiteQuery->where('id', '!=', $exceptWebsiteId);
        }
        if ($websiteQuery->exists()) {
            return $this->fail('Domain is already used as a primary domain by another website', 409);
        }

        $domainAliasQuery = $domainAliasModel::where('domain', $domain);
        if (!is_null($exceptWebsiteId)) {
            $domainAliasQuery->where('website_id', '!=', $exceptWebsiteId);
        }
        if ($domainAliasQuery->exists()) {
            return $this->fail('Domain alias is already assigned to another website', 409);
        }

        return null;
    }

    protected function guardWebsiteApi(string $key)
    {
        if ($err = $this->checkEnabled('enable_api_website')) return $err;
        if ($err = $this->checkEnabled($key)) return $err;
        return null;
    }

    protected function normalizeDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return null;
        }

        $candidate = str_contains($domain, '://') ? $domain : "https://{$domain}";
        $host = parse_url($candidate, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }
}
