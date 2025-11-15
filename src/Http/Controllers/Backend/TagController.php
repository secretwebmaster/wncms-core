<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Imports\TagImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Wncms\Models\Tag;
use Wncms\Models\TagKeyword;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class TagController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $selectedType = $type ?? $request->type ?? 'post_category';
        if ($selectedType != 'all') {
            $q->where('type', $selectedType);
            $related_model_plural_name = explode("_", $selectedType)[0] ?? '';

            if (method_exists($this->modelClass, $related_model_plural_name) && !in_array($related_model_plural_name, ['update'])) {
                $q->withCount("$related_model_plural_name as models_count");
            }
        }

        $q->whereNull('parent_id');
        $q->with('children');

        if ($request->keyword) {
            $q->where('name', 'like', "%$request->keyword%")
                ->orWhereHas('translations', function ($subq) use ($request) {
                    $subq->where('value', 'like', "%$request->keyword%");
                })
                ->orWhere('slug', 'like', "%$request->keyword%");
        }

        if (in_array($request->sort, $this->modelClass::SORTS)) {
            $q->orderBy($request->sort, $request->direction ?? 'desc');
        }

        $q->orderBy('sort', 'desc');

        $parents = $q->paginate($request->page_size ?? 50);
        $tagTypes = wncms()->tag()->getAllTagTypes();

        $allParents = $this->modelClass::whereNull('parent_id')->when($request->type, function ($subq) use ($request) {
            $subq->where('type', $request->type);
        })->get();

        return $this->view('backend.tags.index', [
            'page_title' => __('wncms::word.category_management'),
            'parents' => $parents,
            'allParents' => $allParents,
            'sorts' => $this->modelClass::SORTS,
            'tagTypes' => $tagTypes,
            'type' => $selectedType,
            'hideToolbarCreateButton' => true,
        ]);
    }

    public function create($id = null)
    {
        // Get allowed tag types registered by all models
        $tagTypes = wncms()->tag()->getAllTagTypes();

        $request = request();

        // Load parent tags if type is selected
        if ($request->type) {
            $parents = $this->modelClass::whereType($request->type)
                ->whereNull('parent_id')
                ->with('children')
                ->get();
        } else {
            $parents = [];
        }

        // Existing or new tag
        if ($id) {
            $tag = $this->modelClass::find($id);
            if (!$tag) {
                return back()->withMessage(__('wncms::word.model_not_found', [
                    'model_name' => __('wncms::word.' . $this->singular)
                ]));
            }
        } else {
            $tag = new $this->modelClass;
        }

        $modelGroups = collect(wncms()->getModels())
            ->map(fn($model) => $model::$modelKey ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->view('backend.tags.create', [
            'page_title' => __('wncms::word.category_management'),
            'tagTypes'   => $tagTypes,
            'parents'    => $parents,
            'tag'        => $tag,
            'modelGroups' => $modelGroups,
        ]);
    }

    public function store(Request $request)
    {
        $icon_name = str_replace('<i class="', '', $request->icon);
        $icon_name = str_replace('"></i>', '', $icon_name);

        if (empty($request->type)) {
            return back()->withErrors(['message' => __('wncms::word.tag_type_is_required')]);
        }

        if (!empty($request->slug)) {
            $existing_tag = $this->modelClass::where('type', $request->type)->where('slug', $request->slug)->first();
            if ($existing_tag) {
                return back()->withErrors(['message' => __('wncms::word.tag_with_same_slug_already_exists', ['id' => $existing_tag->id])]);
            }
        }

        $existingTag = $this->modelClass::findFromString($request->name, $request->type);
        if ($existingTag) return back()->withInput()->withErrors(['message' => __('wncms::word.tag_with_same_name_already_exists')]);

        $tag = $this->modelClass::Create([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'slug' => $request->slug ?? $request->name,
            'description' => $request->description,
            'icon' => $icon_name,
            'sort' => $request->sort,
            'type' => $request->type ?? 'post_category',
            'group' => $request->group ?? (is_string($request->type) ? explode('_', $request->type)[0] : null),
        ]);

        if (!empty($request->tag_thumbnail_remove)) $tag->clearMediaCollection('tag_thumbnail');
        if (!empty($request->tag_thumbnail)) $tag->addMediaFromRequest('tag_thumbnail')->toMediaCollection('tag_thumbnail');

        if (!empty($request->tag_background_remove)) $tag->clearMediaCollection('tag_background');
        if (!empty($request->tag_background)) $tag->addMediaFromRequest('tag_background')->toMediaCollection('tag_background');

        $this->flush();
        $this->flush(['page']);

        return redirect()->route('tags.index', ['type' => $request->type])->withInput()->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $tag = $this->modelClass::find($id);
        if (!$tag) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $tagTypes = wncms()->tag()->getAllTagTypes();

        $modelGroups = collect(wncms()->getModels())
            ->map(fn($model) => $model::$modelKey ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return $this->view('backend.tags.edit', [
            'page_title' => __('wncms::word.edit_tag'),
            'tagTypes' => $tagTypes,
            'tag' => $tag,
            'modelGroups' => $modelGroups,
        ]);
    }

    public function update(Request $request, $id)
    {
        $tag = $this->modelClass::find($id);
        if (!$tag) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        if (empty($request->type)) {
            return back()->withErrors(['message' => __('wncms::word.tag_type_is_required')]);
        }

        $existing_tag = $this->modelClass::where('type', $request->type)->where('slug', $request->slug)->where('id', '<>', $tag?->id)->first();
        if ($existing_tag) {
            return back()->withErrors(['message' => __('wncms::word.tag_with_same_slug_already_exists', ['id' => $existing_tag->id])]);
        }

        $icon_name = $request->icon;
        $icon_name = str_replace('<i class="', '', $icon_name);
        $icon_name = str_replace('"></i>', '', $icon_name);

        if (app()->getLocale() != LaravelLocalization::getDefaultLocale()) {
            $newName = $tag->getTranslation('name', LaravelLocalization::getDefaultLocale());
        } else {
            $newName = $request->name;
        }

        $tag->update([
            'parent_id' => $request->parent_id,
            'name' => $newName,
            'type' => $request->type,
            'slug' => $request->slug,
            'description' => $request->description,
            'icon' => $icon_name,
            'sort' => $request->sort,
            'group' => $request->group ?? (is_string($request->type) ? explode('_', $request->type)[0] : null),
        ]);

        if (app()->getLocale() != LaravelLocalization::getDefaultLocale()) {
            $tag->translations()->updateOrCreate(
                [
                    'field' => 'name',
                    'locale' => app()->getLocale(),
                ],
                [
                    'value' => $request->name,
                ]
            );
        }

        if (!empty($request->tag_thumbnail_remove)) $tag->clearMediaCollection('tag_thumbnail');
        if (!empty($request->tag_thumbnail)) $tag->addMediaFromRequest('tag_thumbnail')->toMediaCollection('tag_thumbnail');

        if (!empty($request->tag_background_remove)) $tag->clearMediaCollection('tag_background');
        if (!empty($request->tag_background)) $tag->addMediaFromRequest('tag_background')->toMediaCollection('tag_background');

        $this->flush();
        $this->flush(['page']);

        return redirect()->route('tags.edit', $tag)->withInput()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function bulk_create()
    {
        $placeholder = "測試分類1|slug01|post_category|描述1|0|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";

        $this->flush();

        return $this->view('backend.tags.bulk_create', [
            'page_title' => __('wncms::word.category_management'),
            'placeholder' => $placeholder,
        ]);
    }

    public function bulk_store(Request $request)
    {
        $inputs = explode("\r\n", $request->bulk_tag_data_input);
        $locale = wncms()->getLocale();
        $count = 0;
        $separator = strpos($request->bulk_tag_data_input, "\t") !== false ? "\t" : "|";

        foreach ($inputs as $input) {
            $tagData = explode($separator, $input);

            $name = $tagData[0] ?? null;
            $slug = !empty($tagData[1]) ? $tagData[1] : wncms()->getUniqueSlug('tags');
            $type = $tagData[2] ?? 'post_category';
            $description = $tagData[3] ?? null;
            $parentName = $tagData[4] ?? null;
            $icon = $tagData[5] ?? null;
            $sort = $tagData[6] ?? null;

            if (empty($name)) continue;

            $parent = $this->modelClass::where('type', $type)->where("name->$locale", $parentName)->first();

            $tag = $this->modelClass::firstOrCreate(
                [
                    'name' => $name,
                ],
                [
                    'slug' => $slug,
                    'type' => $type,
                    'description' => $description,
                    'parent_id' => $parent?->id,
                    'icon' => $icon,
                    'sort' => $sort,
                ]
            );

            if ($tag->wasRecentlyCreated) {
                $count++;
            }
        }

        $this->flush(['page']);
        $this->flush();

        return back()->withMessage(__('wncms::word.successfully_created_count', ['count' => $count]));
    }

    public function import_csv(Request $request)
    {
        if ($request->hasFile('csv_file')) {
            Excel::import(new TagImport($request), $request->file('csv_file'));
        }

        $this->flush(['page']);
        $this->flush();

        if (session('current_url')) {
            return redirect(session('current_url'))->withInput()->withMessage(__('wncms::word.successfully_created'));
        }
        return redirect()->route('tags.index')->withInput()->withMessage(__('wncms::word.successfully_created'));
    }

    public function show_keyword_index(Request $request)
    {
        $q = $this->modelClass::query();

        $selectedType = $type ?? $request->type ?? 'post_category';
        if ($selectedType != 'all') {
            $q->where('type', $selectedType);
        }

        $q->whereNull('parent_id');
        $q->with('children');

        if ($request->keyword) {
            $q->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%")
                ->orWhereHas('children', function ($subq) use ($request) {
                    $subq->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%");
                })
                ->orWhereHas('children.children', function ($subq) use ($request) {
                    $subq->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%");
                });
        }

        if (in_array($request->sort, $this->modelClass::SORTS)) {
            $q->orderBy($request->sort, $request->direction ?? 'desc');
        }

        if ($request->tag_type) {
            $q->where('type', $request->tag_type);
        }

        $q->orderBy('sort', 'desc');

        $parents = $q->paginate($request->page_size ?? 50);

        $tagTypes = wncms()->tag()->getAllTagTypes();

        $allKeywords = TagKeyword::whereRelation('tag', 'type', $selectedType)->get()->map(function ($keyword) {
            return ['value' => $keyword->id, 'name' => $keyword->name];
        })->toArray();

        return $this->view('backend.tags.keywords.index', [
            'tagTypes' => $tagTypes,
            'parents' => $parents,
            'allKeywords' => $allKeywords,
            'type' => $selectedType,
        ]);
    }

    public function update_keyword(Request $request, Tag $tag)
    {
        $keywordsToUpdate = collect(json_decode($request->tag_keywords, true))->pluck('name')->toArray();

        foreach ($keywordsToUpdate as $keywordName) {
            $tag->keywords()->updateOrCreate(['name' => $keywordName]);
        }

        $keywordNameList = $tag->keywords()->pluck('name')->toArray();
        $removingKeywords = array_diff($keywordNameList, $keywordsToUpdate);

        $tag->keywords()->whereIn('name', $removingKeywords)->delete();

        return redirect()->route('tags.keywords.index', [
            'type' => $tag->type,
        ])->withMessage(__('wncms::word.tag_keywords_are_updated'));
    }

    public function create_type()
    {
        return $this->view('backend.tags.types.create', [
            'page_title' => wncms_model_word('tag', 'management'),
        ]);
    }

    public function store_type(Request $request)
    {
        $tag = $this->modelClass::findOrCreate(__('wncms::word.default'), $request->slug);

        return redirect()->route('tags.edit', [
            'tag' => $tag,
        ]);
    }

    public function bulk_set_parent(Request $request)
    {
        parse_str($request->formData, $formData);

        $parent = $this->modelClass::find($formData['parent_id']);
        if (!$parent) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.parent_tag_is_not_found'),
                'reload' => true,
            ]);
        }

        if (empty($request->model_ids)) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.tags_are_not_found'),
                'reload' => true,
            ]);
        }

        $tags = $this->modelClass::whereIn('id', $request->model_ids)->update([
            'parent_id' => $parent->id,
        ]);

        if ($tags) {
            $this->flush();

            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
                'reload' => true,
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.tags_are_not_updated'),
                'reload' => true,
            ]);
        }
    }

    public function get_languages(Request $request)
    {
        if (empty($request->model_id)) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_id_is_not_found'),
            ]);
        }

        $tag = $this->modelClass::find($request->model_id);

        if (!$tag) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_is_not_found'),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created'),
            'translations' => $tag->getTranslations(),
        ]);
    }
}
