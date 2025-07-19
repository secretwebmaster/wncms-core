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
        //記錄當前請求地址，用於返回
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

        // $q->withCount('model_count');

        if ($request->keyword) {
            $q->where('name', 'like', "%$request->keyword%")
                ->orWhereHas('translations', function ($subq) use ($request) {
                    $subq->where('value', 'like', "%$request->keyword%");
                })
                ->orWhere('slug', 'like', "%$request->keyword%");
        }

        if (in_array($request->order, $this->modelClass::ORDERS)) {
            $q->orderBy($request->order, $request->sort ?? 'desc');
        }

        $q->orderBy('order_column', 'desc');

        $parents = $q->paginate($request->page_size ?? 50);

        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();

        //for binding 
        $allParents = $this->modelClass::whereNull('parent_id')->when($request->type, function ($subq) use ($request) {
            $subq->where('type', $request->type);
        })->get();

        return $this->view('backend.tags.index', [
            'page_title' => __('wncms::word.category_management'),
            'parents' => $parents,
            'allParents' => $allParents,
            'orders' => $this->modelClass::ORDERS,
            'tagTypes' => $tagTypes,
            'type' => $selectedType,
        ]);
    }

    public function create($id = null)
    {
        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();

        if ($request->type) {
            $parents = $this->modelClass::whereType($request->type)->whereNull('parent_id')->with('children')->get();
        } else {
            $parents = [];
        }

        if ($id) {
            $tag = $this->modelClass::find($id);
            if (!$tag) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $tag = new $this->modelClass;
        }

        return $this->view('backend.tags.create', [
            'page_title' => __('wncms::word.category_management'),
            'tagTypes' => $tagTypes,
            'parents' => $parents,
            'tag' => $tag,
        ]);
    }

    public function store(Request $request)
    {
        //去除fontawesome標籤
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
            'order_column' => $request->order_column,
            'type' => $request->type ?? 'post_category',
        ]);

        //handle tag_thumbnail
        if (!empty($request->tag_thumbnail_remove)) $tag->clearMediaCollection('tag_thumbnail');
        if (!empty($request->tag_thumbnail)) $tag->addMediaFromRequest('tag_thumbnail')->toMediaCollection('tag_thumbnail');

        //handle tag_background
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

        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();
        return $this->view('backend.tags.edit', [
            'page_title' => __('wncms::word.edit_tag'),
            'tagTypes' => $tagTypes,
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());

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

        //去除fontawesome標籤
        $icon_name = str_replace('<i class="', '', $icon_name);
        $icon_name = str_replace('"></i>', '', $icon_name);

        //TODO: check if selected parent_id is one of the children 
        // if (!empty($request->parent_id)) {
        //     $children_ids = $tag->descendants()->pluck('id')->toArray();
        //     if (in_array($request->parent_id, $children_ids)) {
        //         return back()->withErrors(['message' => __('wncms::word.cannot_set_child_as_parent')]);
        //     };
        // }

        if (app()->getLocale() != LaravelLocalization::getDefaultLocale()) {
            $newName = $tag->getTranslation('name', LaravelLocalization::getDefaultLocale());
        } else {
            $newName = $request->name;
        }

        // dd($newName);

        $tag->update([
            'parent_id' => $request->parent_id,
            'name' => $newName,
            'type' => $request->type,
            'slug' => $request->slug,
            'description' => $request->description,
            'icon' => $icon_name,
            'order_column' => $request->order_column,
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

        //handle tag_thumbnail
        if (!empty($request->tag_thumbnail_remove)) $tag->clearMediaCollection('tag_thumbnail');
        if (!empty($request->tag_thumbnail)) $tag->addMediaFromRequest('tag_thumbnail')->toMediaCollection('tag_thumbnail');

        //handle tag_background
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

    /**
     * ----------------------------------------------------------------------------------------------------
     * Bulk create tags with relationships
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param Request $request textarea input from frontend
     *      Format:
     *      name|slug|type|description|parentName|icon|order_column
     *      name|slug|type|description|parentName|icon|order_column
     *      
     *      Example:
     *      name|slug|type|description|parentName|icon|order_column
     *      name|slug|type|description|parentName|icon|order_column
     *      
     *      Rule:
     *      1. must be in sequence
     *      2. input 0 as empty if need to input next parameters
     *      3. parent should be input before children
     * 
     *      After explode:
     *      $tagData[0] = name          | required  | should be string
     *      $tagData[1] = slug          | optional  | Default = random unique 8 letters and numbers combination
     *      $tagData[2] = type          | optional  | Default = "post_category"
     *      $tagData[3] = description   | optional  | Default = null
     *      $tagData[4] = parentName    | optional  | Default = null
     *      $tagData[5] = icon          | optional  | Default = null
     *      $tagData[6] = order_column  | optional  | Default = null
     * 
     * 
     * ----------------------------------------------------------------------------------------------------
     */
    public function bulk_store(Request $request)
    {
        // dd($request->all());
        $inputs = explode("\r\n", $request->bulk_tag_data_input);
        $locale = wncms()->getLocale();
        $count = 0;
        if (strpos($request->bulk_tag_data_input, "\t") !== false) {
            $separator = "\t";
        } else {
            $separator = "|";
        }
        // dd($separator);

        foreach ($inputs as $input) {
            $tagData = explode($separator, $input);
            $name = $tagData[0] ?? null;
            $slug = !empty($tagData[1]) ? $tagData[1] : wncms()->getUniqueSlug('tags');
            $type = $tagData[2] ?? 'post_category';
            $description = $tagData[3] ?? null;
            $parentName = $tagData[4] ?? null;
            $icon = $tagData[5] ?? null;
            $orderColumn = $tagData[6] ?? null;

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
                    'order_column' => $orderColumn,
                ]
            );

            if ($tag->wasRecentlyCreated) {
                $count++;
            }
        }

        $this->flush(['page']);
        $this->flush();

        //返回之前的index
        return back()->withMessage(__('wncms::word.successfully_created_count', ['count' => $count]));
    }

    public function import_csv(Request $request)
    {
        if ($request->hasFile('csv_file')) {
            Excel::import(new TagImport($request), $request->file('csv_file'));
        }

        //clear cache
        $this->flush(['page']);
        $this->flush();

        //返回之前的index
        if (session('current_url')) {
            return redirect(session('current_url'))->withInput()->withMessage(__('wncms::word.successfully_created'));
        }
        return redirect()->route('tags.index')->withInput()->withMessage(__('wncms::word.successfully_created'));
    }

    //! Keywords
    public function show_keyword_index(Request $request)
    {
        $q = $this->modelClass::query();

        $selectedType = $type ?? $request->type ?? 'post_category';
        if ($selectedType != 'all') {
            $q->where('type', $selectedType);
        }

        $q->whereNull('parent_id');
        $q->with('children');
        // $q->withCount('model_count');

        if ($request->keyword) {
            $q->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%")
                ->orWhereHas('children', function ($subq) use ($request) {
                    $subq->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%");
                })
                ->orWhereHas('children.children', function ($subq) use ($request) {
                    $subq->where('name->' . LaravelLocalization::getCurrentLocale(), 'like', "%$request->keyword%");
                });
        }

        if (in_array($request->order, $this->modelClass::ORDERS)) {
            $q->orderBy($request->order, $request->sort ?? 'desc');
        }

        if ($request->tag_type) {
            $q->where('type', $request->tag_type);
        }

        $q->orderBy('order_column', 'desc');
        $parents = $q->paginate($request->page_size ?? 50);

        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();

        $allKeywords = TagKeyword::whereRelation('tag', 'type', $selectedType)->get()->map(function ($keyword) {
            return ['value' => $keyword->id, 'name' => $keyword->name];
        })->toArray();
        // dd($selectedType,$allKeywords);

        return $this->view('backend.tags.keywords.index', [
            'tagTypes' => $tagTypes,
            'parents' => $parents,
            'allKeywords' => $allKeywords,
            'type' => $selectedType,
        ]);
    }

    public function update_keyword(Request $request, Tag $tag)
    {
        // dd($request->all());
        $keywordsToUpdate = collect(json_decode($request->tag_keywords, true))->pluck('name')->toArray();

        //attach tag if name not in list
        foreach ($keywordsToUpdate as $keywordName) {
            $tag->keywords()->updateOrCreate(['name' => $keywordName]);
        }

        //get current tag keyword name list array
        $keywordNameList = $tag->keywords()->pluck('name')->toArray();

        //remove tag not in name list
        $removingKeywords = array_diff($keywordNameList, $keywordsToUpdate);

        $removedKeywords = $tag->keywords()->whereIn('name', $removingKeywords)->delete();

        return redirect()->route('tags.keywords.index', [
            'type' => $tag->type,
        ])->withMessage(__('wncms::word.tag_keywords_are_updated'));
    }

    //! Types
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

    /**
     * ----------------------------------------------------------------------------------------------------
     * 演示功能描述
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.7
     * 
     * @param Request $request 請求
     *  - $reqesut->formData 包括父分類ID interger，例如 "parent_id=1"
     *  - $request->model_ids 子分類ID array
     * @return Illuminate\Http\Response
     * ----------------------------------------------------------------------------------------------------
     */
    public function bulk_set_parent(Request $request)
    {
        // info($request->all());
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
        // info($request->all());
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
