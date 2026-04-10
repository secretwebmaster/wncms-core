<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdvertisementController extends ApiV2Controller
{
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = wncms()->getModelClass('advertisement');
    }

    protected function authorizeResourceAction(string $action): void
    {
        $permission = config("wncms-backend-api-v2.resources.advertisements.permissions.{$action}");
        if (!empty($permission)) {
            abort_unless(auth()->user()?->can($permission), Response::HTTP_FORBIDDEN);
        }
    }

    public function index(Request $request)
    {
        try {
            $this->authorizeResourceAction('index');

            $q = $this->modelClass::query();
            $this->applyModelWebsiteScope($q, $request);

            if (in_array($request->status, $this->modelClass::STATUSES, true)) {
                $q->where('status', $request->status);
            }

            if (in_array($request->position, $this->modelClass::POSITIONS, true)) {
                $q->where('position', $request->position);
            }

            if ($request->keyword) {
                $keyword = (string) $request->keyword;
                $q->where(function ($subq) use ($keyword) {
                    $subq->where('id', $keyword)
                        ->orWhere('cta_text', 'like', "%" . $keyword . "%")
                        ->orWhere('url', 'like', "%" . $keyword . "%")
                        ->orWhere('cta_text_2', 'like', "%" . $keyword . "%")
                        ->orWhere('url_2', 'like', "%" . $keyword . "%")
                        ->orWhere('remark', 'like', "%" . $keyword . "%")
                        ->orWhere('code', 'like', "%" . $keyword . "%")
                        ->orWhere('name', 'like', "%" . $keyword . "%");
                });
            }

            $sort = in_array($request->sort, $this->modelClass::SORTS, true) ? $request->sort : 'sort';
            $direction = in_array($request->direction, ['asc', 'desc'], true) ? $request->direction : 'desc';
            $q->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $q->orderBy('id', 'desc');
            }

            $q->with(['media', 'tags', 'websites']);

            $paginator = $q->paginate((int) ($request->page_size ?? $request->per_page ?? 20));
            $items = collect($paginator->items())->map(fn($item) => $this->transformAdvertisement($item))->values()->all();

            return $this->ok($items, 'success', Response::HTTP_OK, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'statuses' => $this->modelClass::STATUSES,
                'types' => $this->modelClass::TYPES,
                'positions' => $this->modelClass::POSITIONS,
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function show(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('show');

            $model = $this->modelClass::query()
                ->with(['media', 'tags', 'websites'])
                ->find($id);

            if (!$model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            return $this->ok($this->transformAdvertisement($model), 'success');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->authorizeResourceAction('store');

            $validated = $this->validatePayload($request);
            $model = $this->modelClass::query()->create($this->toWritePayload($validated));

            $websiteInput = $request->input('website_ids', $request->input('website_id'));
            $websiteIds = $this->resolveModelWebsiteIds($this->modelClass, $websiteInput);
            $this->syncModelWebsites($model, $websiteIds);

            $this->syncMediaAndTags($request, $model);
            $this->flushAdvertisementCache();

            return $this->ok($this->transformAdvertisement($model->fresh(['media', 'tags', 'websites'])), 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function update(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('update');

            $model = $this->modelClass::query()->find($id);
            if (!$model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $validated = $this->validatePayload($request, true);
            $model->update($this->toWritePayload($validated));

            if ($request->has('website_ids') || $request->has('website_id')) {
                $websiteInput = $request->input('website_ids', $request->input('website_id'));
                $websiteIds = $this->resolveModelWebsiteIds($this->modelClass, $websiteInput);
                $this->syncModelWebsites($model, $websiteIds);
            }

            $this->syncMediaAndTags($request, $model);
            $this->flushAdvertisementCache();

            return $this->ok($this->transformAdvertisement($model->fresh(['media', 'tags', 'websites'])), 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function updateViaPost(Request $request, int|string $id)
    {
        return $this->update($request, $id);
    }

    public function destroy(Request $request, int|string $id)
    {
        try {
            $this->authorizeResourceAction('destroy');

            $model = $this->modelClass::query()->find($id);
            if (!$model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $model->delete();
            $this->flushAdvertisementCache();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function destroyViaPost(Request $request, int|string $id)
    {
        return $this->destroy($request, $id);
    }

    protected function applyModelWebsiteScope($query, Request $request): void
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

    protected function validatePayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate(
            [
                'status' => [$isUpdate ? 'sometimes' : 'required', Rule::in($this->modelClass::STATUSES)],
                'type' => [$isUpdate ? 'sometimes' : 'required', Rule::in($this->modelClass::TYPES)],
                'position' => ['nullable', Rule::in($this->modelClass::POSITIONS)],
                'expired_at' => 'nullable|date',
                'name' => 'nullable|string|max:255',
                'cta_text' => 'nullable|string|max:255',
                'url' => 'nullable|string|max:255',
                'cta_text_2' => 'nullable|string|max:255',
                'url_2' => 'nullable|string|max:255',
                'remark' => 'nullable|string|max:255',
                'text_color' => 'nullable|string|max:50',
                'background_color' => 'nullable|string|max:50',
                'code' => 'nullable|string',
                'style' => 'nullable|string',
                'contact' => 'nullable|string|max:255',
                'sort' => 'nullable|integer',
                'advertisement_thumbnail' => 'nullable|file|image|max:20480',
                'advertisement_thumbnail_remove' => 'nullable',
                'advertisement_tags' => 'nullable|string',
                'website_id' => 'nullable|integer|exists:websites,id',
                'website_ids' => 'nullable|array',
                'website_ids.*' => 'nullable|integer|exists:websites,id',
            ],
            [
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'type.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.type')]),
                'advertisement_thumbnail.image' => __('wncms::word.invalid_request'),
            ]
        );
    }

    protected function toWritePayload(array $validated): array
    {
        return [
            'status' => $validated['status'] ?? null,
            'expired_at' => $validated['expired_at'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'position' => $validated['position'] ?? null,
            'cta_text' => $validated['cta_text'] ?? null,
            'url' => $validated['url'] ?? null,
            'cta_text_2' => $validated['cta_text_2'] ?? null,
            'url_2' => $validated['url_2'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'text_color' => $validated['text_color'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
            'code' => $validated['code'] ?? null,
            'style' => $validated['style'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'contact' => $validated['contact'] ?? null,
        ];
    }

    protected function syncMediaAndTags(Request $request, $model): void
    {
        if ($request->filled('advertisement_thumbnail_remove')) {
            $model->clearMediaCollection('advertisement_thumbnail');
        }

        if ($request->hasFile('advertisement_thumbnail')) {
            $model->clearMediaCollection('advertisement_thumbnail');
            $model->addMediaFromRequest('advertisement_thumbnail')->toMediaCollection('advertisement_thumbnail');
        }

        if ($request->has('advertisement_tags')) {
            $model->syncTagsFromTagify((string) $request->input('advertisement_tags', ''), 'advertisement_tag');
        }
    }

    protected function transformAdvertisement($model): array
    {
        return [
            'id' => (int) $model->id,
            'status' => (string) ($model->status ?? ''),
            'expired_at' => optional($model->expired_at)?->toISOString(),
            'name' => (string) ($model->name ?? ''),
            'type' => (string) ($model->type ?? ''),
            'position' => (string) ($model->position ?? ''),
            'sort' => $model->sort !== null ? (int) $model->sort : null,
            'cta_text' => (string) ($model->cta_text ?? ''),
            'url' => (string) ($model->url ?? ''),
            'cta_text_2' => (string) ($model->cta_text_2 ?? ''),
            'url_2' => (string) ($model->url_2 ?? ''),
            'remark' => (string) ($model->remark ?? ''),
            'text_color' => (string) ($model->text_color ?? ''),
            'background_color' => (string) ($model->background_color ?? ''),
            'code' => (string) ($model->code ?? ''),
            'style' => (string) ($model->style ?? ''),
            'contact' => (string) ($model->contact ?? ''),
            'thumbnail' => (string) ($model->thumbnail ?? ''),
            'website_ids' => $model->relationLoaded('websites')
                ? $model->websites->pluck('id')->map(fn($id) => (int) $id)->values()->all()
                : [],
            'advertisement_tags' => method_exists($model, 'tagsWithType')
                ? $model->tagsWithType('advertisement_tag')->implode('name', ',')
                : '',
            'created_at' => optional($model->created_at)?->toISOString(),
            'updated_at' => optional($model->updated_at)?->toISOString(),
        ];
    }

    protected function flushAdvertisementCache(): void
    {
        wncms()->cache()->tags(['advertisements', 'tags', 'websites'])->flush();
    }
}
