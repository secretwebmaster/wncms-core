<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Translatable\Models\Translation;

class PostController extends ApiV2Controller
{
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = wncms()->getModelClass('post');
    }

    public function index(Request $request)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.index'));

            $q = $this->modelClass::query();
            $this->applyPostWebsiteScope($q, $request);

            if (!isAdmin()) {
                $q->whereRelation('user', 'id', auth()->id());
            }

            if (in_array($request->status, $this->modelClass::STATUSES, true)) {
                $q->where('status', $request->status);
            }

            if ($request->keyword) {
                $keyword = (string) $request->keyword;
                $q->where(function ($builder) use ($keyword) {
                    $builder->where('slug', 'like', "%{$keyword}%")
                        ->orWhere('id', $keyword)
                        ->orWhere('slug', $keyword)
                        ->orWhere('title', 'like', "%{$keyword}%");
                });
            }

            if ($request->category) {
                $q->withAnyTags([(string) $request->category], 'post_category');
            }

            if ($request->boolean('show_trashed')) {
                $q->withTrashed();
            }

            if (in_array($request->sort, $this->modelClass::SORTS, true)) {
                $direction = in_array($request->direction, ['asc', 'desc'], true) ? $request->direction : 'desc';
                $sort = in_array($request->sort, ['traffics', 'clicks'], true)
                    ? $request->sort . '_count'
                    : $request->sort;
                $q->orderBy($sort, $direction);
            }

            $q->with(['media', 'tags', 'user', 'websites']);
            $q->orderBy('created_at', 'desc');
            $q->orderBy('id', 'desc');

            $paginator = $q->paginate((int) ($request->page_size ?? $request->per_page ?? 20));

            return $this->ok(
                array_map(fn ($post) => $this->transformPostListItem($post), $paginator->items()),
                'success',
                Response::HTTP_OK,
                [
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                    'statuses' => $this->modelClass::STATUSES,
                    'visibilities' => $this->modelClass::VISIBILITIES,
                    'sorts' => $this->modelClass::SORTS,
                    'post_categories' => wncms()->tag()->getArray(tagType: 'post_category', columnName: 'name'),
                    'post_tags' => wncms()->tag()->getArray(tagType: 'post_tag', columnName: 'name'),
                ]
            );
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function show(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.show'));

            $commentOrder = $request->query('comments_order', 'newest');
            if (!in_array($commentOrder, ['newest', 'oldest'], true)) {
                $commentOrder = 'newest';
            }

            $post = $this->modelClass::withTrashed()
                ->with(['media', 'tags', 'user', 'websites'])
                ->find($id);

            if (!$post) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            if (!isAdmin() && (int) $post->user_id !== (int) auth()->id()) {
                return $this->error(__('auth.unauthorized'), Response::HTTP_FORBIDDEN);
            }

            $translations = $this->resolvePostTranslations($post);
            $commentsQuery = $post->comments()
                ->whereNull('parent_id')
                ->with(['user', 'children', 'children.user']);

            if ($commentOrder === 'oldest') {
                $commentsQuery->oldest();
            } else {
                $commentsQuery->latest();
            }

            return $this->ok([
                ...$this->transformPostDetail($post),
                'translations' => $translations['values'],
                'comments' => $commentsQuery->get()->map(fn ($comment) => $this->transformComment($comment))->values(),
            ], 'success', Response::HTTP_OK, [
                ...$this->buildPostMeta($post->user_id),
                'comment_statuses' => wncms()->getModel('comment')::STATUSES,
                'comment_order' => $commentOrder,
                'locales' => $translations['locales'],
                'current_locale' => app()->getLocale(),
                'fallback_locale' => app()->getFallbackLocale(),
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function meta(Request $request)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.index'));

            return $this->ok(
                null,
                'success',
                Response::HTTP_OK,
                [
                    ...$this->buildPostMeta((int) $request->input('selected_user_id')),
                    'locales' => $this->resolveSupportedLocales(),
                    'current_locale' => app()->getLocale(),
                    'fallback_locale' => app()->getFallbackLocale(),
                    'comment_statuses' => wncms()->getModel('comment')::STATUSES,
                ]
            );
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.store'));

            $user = $this->resolveMutationUser($request);
            if (!$user) {
                return $this->error(__('wncms::word.user_not_found'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->validatePostPayload($request);
            $normalizedTranslatable = $this->getNormalizedTranslatableInputs(
                $request,
                $this->modelClass,
                ['title', 'excerpt', 'keywords', 'content', 'label']
            );
            $this->mergeTranslatableBaseValuesIntoRequest($request, $normalizedTranslatable);

            $post = $user->posts()->create($this->buildPostPayload($request));
            $this->applyModelTranslations($post, $normalizedTranslatable);
            $this->afterPostMutation($request, $post, false);

            return $this->ok($this->transformPostDetail($post->fresh(['media', 'tags', 'user', 'websites'])), 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function update(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.update'));

            $post = $this->modelClass::withTrashed()->find($id);
            if (!$post) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $user = $this->resolveMutationUser($request, $post);
            if (!$user) {
                return $this->error(__('wncms::word.user_not_found'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->validatePostPayload($request, $post);
            $normalizedTranslatable = $this->getNormalizedTranslatableInputs(
                $request,
                $this->modelClass,
                ['title', 'excerpt', 'keywords', 'content', 'label']
            );
            $this->mergeTranslatableBaseValuesIntoRequest($request, $normalizedTranslatable);

            $post->update($this->buildPostPayload($request, $user->id, $post));
            $this->applyModelTranslations($post, $normalizedTranslatable);
            $this->afterPostMutation($request, $post, true);

            return $this->ok($this->transformPostDetail($post->fresh(['media', 'tags', 'user', 'websites'])), 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function destroy(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.destroy'));

            $post = $this->modelClass::withTrashed()->find($id);
            if (!$post) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            if (!isAdmin() && (int) $post->user_id !== (int) auth()->id()) {
                return $this->error(__('auth.unauthorized'), Response::HTTP_FORBIDDEN);
            }

            $post->update(['status' => 'trashed']);
            $post->delete();
            $this->flushPostCache();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function deleteViaPost(Request $request, int|string $id)
    {
        return $this->destroy($request, $id);
    }

    public function restore(Request $request, int|string $id)
    {
        try {
            $post = $this->modelClass::withTrashed()->find($id);
            if (!$post) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            if (!isAdmin() && (int) $post->user_id !== (int) auth()->id()) {
                return $this->error(__('auth.unauthorized'), Response::HTTP_FORBIDDEN);
            }

            $post->update([
                'status' => gss('restore_trashed_content_to_published') ? 'published' : 'drafted',
            ]);
            $post->restore();

            $this->flushPostCache();

            return $this->ok(
                $this->transformPostDetail($post->fresh(['media', 'tags', 'user', 'websites'])),
                'successfully_restored'
            );
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->authorizeResourceAction('post_bulk_delete');

            $modelIds = $request->input('model_ids', []);
            if (!is_array($modelIds)) {
                $modelIds = array_filter(explode(',', (string) $modelIds));
            }

            $modelIds = array_values(array_unique(array_filter(array_map('intval', $modelIds))));
            if (empty($modelIds)) {
                return $this->error(__('wncms::word.model_ids_are_not_found'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $query = $this->modelClass::withTrashed()->whereIn('id', $modelIds);
            if (!isAdmin()) {
                $query->where('user_id', auth()->id());
            }

            $posts = $query->get();
            if ($posts->isEmpty()) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $count = 0;
            foreach ($posts as $post) {
                $post->update(['status' => 'trashed']);
                $post->delete();
                $count++;
            }

            $this->flushPostCache();

            return $this->ok(['deleted' => $count], 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function translations(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction(config('wncms-backend-api-v2.resources.posts.permissions.show'));

            $post = $this->modelClass::withTrashed()->find($id);
            if (!$post) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            if (!isAdmin() && (int) $post->user_id !== (int) auth()->id()) {
                return $this->error(__('auth.unauthorized'), Response::HTTP_FORBIDDEN);
            }

            $translations = $this->resolvePostTranslations($post);

            return $this->ok([
                'id' => (int) $post->id,
                'translations' => $translations['values'],
            ], 'success', Response::HTTP_OK, [
                'locales' => $translations['locales'],
                'current_locale' => app()->getLocale(),
                'fallback_locale' => app()->getFallbackLocale(),
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    protected function authorizeResourceAction(?string $permission): void
    {
        if (!empty($permission)) {
            abort_unless(auth()->user()?->can($permission), Response::HTTP_FORBIDDEN);
        }
    }

    protected function applyPostWebsiteScope($query, Request $request): void
    {
        if (
            !method_exists($this->modelClass, 'applyWebsiteScope')
            || !method_exists($this->modelClass, 'getWebsiteMode')
            || !in_array($this->modelClass::getWebsiteMode(), ['single', 'multi'], true)
        ) {
            return;
        }

        $websiteId = (int) ($request->input('website_id') ?? $request->input('website') ?? 0);
        if ($websiteId <= 0) {
            return;
        }

        $this->modelClass::applyWebsiteScope($query, $websiteId);
    }

    protected function resolveMutationUser(Request $request, $post = null)
    {
        if (!isAdmin()) {
            if ($post && (int) $post->user_id !== (int) auth()->id()) {
                abort(Response::HTTP_FORBIDDEN, __('wncms::word.invalid_request'));
            }

            return auth()->user();
        }

        $userId = $request->input('user_id');
        if ($userId) {
            return wncms()->getModel('user')::find($userId) ?? auth()->user();
        }

        if ($post?->user_id) {
            return wncms()->getModel('user')::find($post->user_id) ?? auth()->user();
        }

        return auth()->user();
    }

    protected function validatePostPayload(Request $request, $post = null): void
    {
        $request->validate(
            [
                'title' => 'required|max:255',
                'status' => ['required', Rule::in($this->modelClass::STATUSES)],
                'visibility' => ['required', Rule::in($this->modelClass::VISIBILITIES)],
                'price' => 'sometimes|nullable|numeric|max:999999.999',
                'user_id' => 'sometimes|nullable|exists:users,id',
                'published_at' => 'nullable|date',
                'expired_at' => 'nullable|date',
                'slug' => [
                    'nullable',
                    Rule::unique((new $this->modelClass)->getTable(), 'slug')->ignore($post?->id),
                ],
            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
                'price.max' => __('wncms::word.field_should_not_exceed', ['field_name' => __('wncms::word.price'), 'value' => '999999.999']),
                'slug.unique' => __('wncms::word.duplicated_slug'),
            ]
        );
    }

    protected function buildPostPayload(Request $request, ?int $userId = null, $post = null): array
    {
        return [
            'user_id' => $userId ?? $post?->user_id ?? auth()->id(),
            'status' => $request->input('status'),
            'visibility' => $request->input('visibility'),
            'external_thumbnail' => $request->input('external_thumbnail'),
            'slug' => $request->input('slug') ?: ($post?->slug ?: wncms()->getUniqueSlug('posts')),
            'title' => $request->input('title'),
            'label' => $request->input('label'),
            'excerpt' => $request->input('excerpt'),
            'content' => $request->input('content'),
            'remark' => $request->input('remark'),
            'sort' => $request->input('sort'),
            'password' => $request->input('password'),
            'price' => $request->input('price'),
            'is_pinned' => $request->boolean('is_pinned'),
            'is_recommended' => $request->boolean('is_recommended'),
            'is_dmca' => $request->boolean('is_dmca'),
            'published_at' => $request->input('published_at') ? Carbon::parse($request->input('published_at')) : Carbon::now(),
            'expired_at' => $request->input('expired_at') ? Carbon::parse($request->input('expired_at')) : null,
        ];
    }

    protected function afterPostMutation(Request $request, $post, bool $isUpdate): void
    {
        $post->localizeImages();
        $post->wrapTables();

        $websiteIds = $this->resolvePostWebsiteIds();
        $this->syncModelWebsites($post, $websiteIds);

        if ($request->boolean('post_thumbnail_remove')) {
            $post->clearMediaCollection('post_thumbnail');
        }

        if ($request->hasFile('post_thumbnail')) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        if ($isUpdate) {
            $post->syncTagsWithType($this->normalizeTagValues($request->input('post_categories')), 'post_category');
            $post->syncTagsWithType($this->normalizeTagValues($request->input('post_tags')), 'post_tag');
        } else {
            $bindingContents = [
                'title' => (string) $request->input('title'),
                'content' => (string) $request->input('content'),
                'excerpt' => (string) $request->input('excerpt'),
                'keywords' => (string) $request->input('keywords'),
                'label' => (string) $request->input('label'),
                'remark' => (string) $request->input('remark'),
                'slug' => (string) $request->input('slug'),
            ];

            $post->syncTagsFromRequest(
                $this->normalizeTagifyInput($request->input('post_categories')),
                'post_category',
                $request->boolean('auto_generate_category'),
                $bindingContents
            );
            $post->syncTagsFromRequest(
                $this->normalizeTagifyInput($request->input('post_tags')),
                'post_tag',
                $request->boolean('auto_generate_tag'),
                $bindingContents
            );
        }

        $this->flushPostCache();
    }

    protected function resolvePostWebsiteIds(): array
    {
        $websiteIds = $this->resolveModelWebsiteIds($this->modelClass);

        if (!isAdmin()) {
            $allowedWebsiteIds = auth()->user()?->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->values()->all() ?? [];
            $websiteIds = array_values(array_intersect($websiteIds, $allowedWebsiteIds));
        }

        return $websiteIds;
    }

    protected function normalizeTagValues(mixed $input): array
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $input)));
        }

        if (!is_string($input) || trim($input) === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map(function ($item) {
                if (is_array($item) && isset($item['value'])) {
                    return trim((string) $item['value']);
                }

                return is_scalar($item) ? trim((string) $item) : '';
            }, $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $input))));
    }

    protected function normalizeTagifyInput(mixed $input): string
    {
        $values = $this->normalizeTagValues($input);
        if (empty($values)) {
            return '';
        }

        return json_encode(array_map(fn ($value) => ['value' => $value], $values), JSON_UNESCAPED_UNICODE);
    }

    protected function resolveUserOptions(?int $selectedUserId = null): array
    {
        $userModel = wncms()->getModel('user');
        $query = $userModel::query()->select(['id', 'username', 'email']);

        if (!isAdmin()) {
            $query->where('id', auth()->id());
        }

        $users = $query->orderBy('username')->limit(50)->get();

        if ($selectedUserId && $users->where('id', $selectedUserId)->isEmpty()) {
            $selectedUser = $userModel::query()->select(['id', 'username', 'email'])->find($selectedUserId);
            if ($selectedUser) {
                $users->prepend($selectedUser);
            }
        }

        return $users->map(fn ($user) => [
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'email' => (string) ($user->email ?? ''),
        ])->unique('id')->values()->all();
    }

    protected function resolveWebsiteOptions(): array
    {
        $websiteModel = wncms()->getModel('website');
        $query = $websiteModel::query()->select(['id', 'domain', 'site_name']);

        if (!isAdmin()) {
            $allowedIds = auth()->user()?->websites()->pluck('websites.id')->values()->all() ?? [];
            $query->whereIn('id', $allowedIds);
        }

        return $query->orderBy('id')->get()->map(fn ($website) => [
            'id' => (int) $website->id,
            'name' => (string) ($website->site_name ?? $website->domain ?? ''),
            'domain' => (string) ($website->domain ?? ''),
        ])->values()->all();
    }

    protected function transformPostListItem($post): array
    {
        return [
            'id' => (int) $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
            'visibility' => $post->visibility,
            'sort' => $post->sort,
            'external_thumbnail' => $post->external_thumbnail,
            'thumbnail' => $post->thumbnail,
            'is_pinned' => (bool) $post->is_pinned,
            'is_recommended' => (bool) $post->is_recommended,
            'published_at' => optional($post->published_at)?->toISOString(),
            'created_at' => optional($post->created_at)?->toISOString(),
            'updated_at' => optional($post->updated_at)?->toISOString(),
            'deleted_at' => optional($post->deleted_at)?->toISOString(),
            'author' => $post->user ? [
                'id' => (int) $post->user->id,
                'username' => (string) ($post->user->username ?? ''),
                'email' => (string) ($post->user->email ?? ''),
            ] : null,
            'website_ids' => $post->relationLoaded('websites')
                ? $post->websites->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'post_categories' => $post->relationLoaded('tags')
                ? $post->tags->where('type', 'post_category')->pluck('name')->values()->all()
                : [],
        ];
    }

    protected function transformPostDetail($post): array
    {
        return [
            'id' => (int) $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
            'visibility' => $post->visibility,
            'external_thumbnail' => $post->external_thumbnail,
            'thumbnail' => $post->thumbnail,
            'label' => $post->label,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'remark' => $post->remark,
            'sort' => $post->sort,
            'password' => $post->password,
            'price' => $post->price,
            'is_pinned' => (bool) $post->is_pinned,
            'is_recommended' => (bool) $post->is_recommended,
            'is_dmca' => (bool) $post->is_dmca,
            'published_at' => optional($post->published_at)?->toISOString(),
            'expired_at' => optional($post->expired_at)?->toISOString(),
            'created_at' => optional($post->created_at)?->toISOString(),
            'updated_at' => optional($post->updated_at)?->toISOString(),
            'user_id' => $post->user_id ? (int) $post->user_id : null,
            'commentable_type' => $post::class,
            'commentable_id' => (int) $post->id,
            'website_ids' => $post->relationLoaded('websites')
                ? $post->websites->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'post_categories' => $post->relationLoaded('tags')
                ? $post->tags->where('type', 'post_category')->pluck('name')->values()->all()
                : [],
            'post_tags' => $post->relationLoaded('tags')
                ? $post->tags->where('type', 'post_tag')->pluck('name')->values()->all()
                : [],
            'author' => $post->user ? [
                'id' => (int) $post->user->id,
                'username' => (string) ($post->user->username ?? ''),
                'email' => (string) ($post->user->email ?? ''),
            ] : null,
        ];
    }

    protected function resolvePostTranslations($post): array
    {
        $translatableFields = method_exists($post, 'getTranslatable')
            ? (array) $post->getTranslatable()
            : [];

        $locales = $this->resolveSupportedLocales();
        $defaultLocale = $this->normalizeLocaleKey((string) app()->getLocale());

        $values = [];
        foreach ($translatableFields as $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }

            $values[$field] = [];
            foreach ($locales as $locale) {
                $values[$field][$locale] = '';
            }

            if (isset($values[$field][$defaultLocale])) {
                $values[$field][$defaultLocale] = (string) ($post->getAttributes()[$field] ?? '');
            }
        }

        if (empty($translatableFields)) {
            return [
                'locales' => $locales,
                'values' => $values,
            ];
        }

        $rows = Translation::query()
            ->select(['field', 'locale', 'value'])
            ->where('translatable_type', get_class($post))
            ->where('translatable_id', (int) $post->id)
            ->whereIn('field', $translatableFields)
            ->get();

        foreach ($rows as $row) {
            $field = (string) $row->field;
            $locale = $this->normalizeLocaleKey((string) $row->locale);

            if (!isset($values[$field])) {
                continue;
            }

            if (!isset($values[$field][$locale])) {
                $values[$field][$locale] = '';
                if (!in_array($locale, $locales, true)) {
                    $locales[] = $locale;
                }
            }

            $values[$field][$locale] = (string) ($row->value ?? '');
        }

        return [
            'locales' => array_values(array_unique(array_filter($locales))),
            'values' => $values,
        ];
    }

    protected function resolveSupportedLocales(): array
    {
        $supported = LaravelLocalization::getSupportedLocales();
        $keys = array_map(fn ($key) => $this->normalizeLocaleKey((string) $key), array_keys((array) $supported));
        $keys[] = $this->normalizeLocaleKey((string) app()->getLocale());
        $keys[] = $this->normalizeLocaleKey((string) app()->getFallbackLocale());

        return array_values(array_unique(array_filter($keys)));
    }

    protected function buildPostMeta(?int $selectedUserId = null): array
    {
        return [
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'sorts' => $this->modelClass::SORTS,
            'post_categories' => wncms()->tag()->getArray(tagType: 'post_category', columnName: 'name'),
            'post_tags' => wncms()->tag()->getArray(tagType: 'post_tag', columnName: 'name'),
            'users' => $this->resolveUserOptions($selectedUserId),
            'websites' => $this->resolveWebsiteOptions(),
        ];
    }

    protected function transformComment($comment): array
    {
        return [
            'id' => (int) $comment->id,
            'content' => (string) $comment->content,
            'status' => (string) $comment->status,
            'created_at' => optional($comment->created_at)?->toISOString(),
            'author' => $comment->user ? [
                'id' => (int) $comment->user->id,
                'username' => (string) ($comment->user->username ?? ''),
                'email' => (string) ($comment->user->email ?? ''),
            ] : null,
            'children' => $comment->children->map(fn ($child) => $this->transformComment($child))->values(),
        ];
    }

    protected function flushPostCache(): void
    {
        wncms()->cache()->tags(['posts'])->flush();
    }
}
