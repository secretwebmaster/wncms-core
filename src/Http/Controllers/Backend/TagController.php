<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Traits\BulkDeleteTraits;
use Wncms\Imports\TagImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Wncms\Models\Tag;
use Wncms\Models\TagKeyword;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


class TagController extends Controller
{
    use BulkDeleteTraits;

    public function index(Request $request, $type = null)
    {
        //記錄當前請求地址，用於返回
        $q = Tag::query();


        $selectedType = $type ?? $request->type ?? 'post_category';
        if($selectedType != 'all'){
            $q->where('type', $selectedType);
            $related_model_plural_name = explode("_", $selectedType)[0] ?? '';

            if(method_exists(Tag::class, $related_model_plural_name) && !in_array($related_model_plural_name, ['update'])){
                $q->withCount("$related_model_plural_name as models_count");
            }
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

        if (in_array($request->order, Tag::ORDERS)) {
            $q->orderBy($request->order, $request->sort ?? 'desc');
        }

        $q->orderBy('order_column', 'desc');

        $parents = $q->paginate($request->page_size ?? 50);

        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();

        //for binding 
        $allParents = Tag::whereNull('parent_id')->when($request->type, function($subq) use($request){
            $subq->where('type', $request->type);
        })->get();

        return view('wncms::backend.tags.index', [
            'page_title' => __('word.category_management'),
            'parents' => $parents,
            'allParents' => $allParents,
            'orders' => Tag::ORDERS,
            'tagTypes' => $tagTypes,
            'type' => $selectedType,
        ]);
    }

    public function create(Request $request, Tag $tag = null)
    {
        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();
 
        if ($request->type) {
            $parents = Tag::whereType($request->type)->whereNull('parent_id')->with('children')->get();
        } else {
            $parents = [];
        }

        $tag ??= new Tag;

        return view('wncms::backend.tags.create', [
            'page_title' => __('word.category_management'),
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
            return back()->withErrors(['message' => __('word.tag_type_is_required')]);
        }

        if(!empty($request->slug)){
            $existing_tag = Tag::where('type', $request->type)->where('slug', $request->slug)->first();
            if ($existing_tag) {
                return back()->withErrors(['message' => __('word.tag_with_same_slug_already_exists', ['id' => $existing_tag->id])]);
            }
        }


        $existingTag = Tag::findFromString($request->name, $request->type);
        if($existingTag) return back()->withInput()->withErrors(['message' => __('word.tag_with_same_name_already_exists')]);

        $tag = Tag::Create([
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

        wncms()->cache()->flush(['pages', 'tags']);


        return redirect()->route('tags.index', ['type' => $request->type])->withInput()->withMessage(__('word.successfully_created'));
    }

    public function edit(Tag $tag)
    {
        dd($tag);
        $tagTypes = wncms()->tag()->getModelsWithHasTagsTraits();
        return view('wncms::backend.tags.edit', [
            'page_title' => __('word.edit_tag'),
            'tagTypes' => $tagTypes,
            // 'tag' => $tag->with_recursive_children(),
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        // dd($request->all());

        if (empty($request->type)) {
            return back()->withErrors(['message' => __('word.tag_type_is_required')]);
        }

        $existing_tag = Tag::where('type', $request->type)->where('slug', $request->slug)->where('id', '<>', $tag?->id)->first();
        if ($existing_tag) {
            return back()->withErrors(['message' => __('word.tag_with_same_slug_already_exists', ['id' => $existing_tag->id])]);
        }

        $icon_name = $request->icon;

        //去除fontawesome標籤
        $icon_name = str_replace('<i class="', '', $icon_name);
        $icon_name = str_replace('"></i>', '', $icon_name);

        //check if selected parent_id is one of the children 
        if (!empty($request->parent_id)) {
            $children_ids = $tag->descendants()->pluck('id')->toArray();
            if (in_array($request->parent_id, $children_ids)) {
                return back()->withErrors(['message' => __('word.cannot_set_child_as_parent')]);
            };
        }

        if(app()->getLocale() != LaravelLocalization::getDefaultLocale()){
            $newName = $tag->getTranslation('name', LaravelLocalization::getDefaultLocale());
        }else{
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

        if(app()->getLocale() != LaravelLocalization::getDefaultLocale()){
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

        // dd(
        //     request()->all(),
        //     app()->getLocale(),
        //     LaravelLocalization::getDefaultLocale(),
        //     $newName,
        //     $tag,
        //     $tag->translations,
        // );


        //handle tag_thumbnail
        if (!empty($request->tag_thumbnail_remove)) $tag->clearMediaCollection('tag_thumbnail');
        if (!empty($request->tag_thumbnail)) $tag->addMediaFromRequest('tag_thumbnail')->toMediaCollection('tag_thumbnail');

        //handle tag_background
        if (!empty($request->tag_background_remove)) $tag->clearMediaCollection('tag_background');
        if (!empty($request->tag_background)) $tag->addMediaFromRequest('tag_background')->toMediaCollection('tag_background');

        wncms()->cache()->flush(['pages', 'tags']);

        return redirect()->route('tags.edit', $tag)->withInput()->withMessage(__('word.successfully_updated'));
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return redirect()->back()->withInput()->with([
            'status' => 'success',
            'message' => __('word.successfully_deleted')
        ]);
    }

    public function bulk_create()
    {
        $placeholder = "測試分類1|slug01|post_category|描述1|0|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";
        $placeholder .= "\r\n測試分類2|slug02|post_category|描述2|測試分類1|0|0";

        return view('wncms::backend.tags.bulk_create', [
            'page_title' => __('word.category_management'),
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
        if(strpos($request->bulk_tag_data_input, "\t") !== false){
            $separator = "\t";
        }else{
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

            $parent = Tag::where('type', $type)->where("name->$locale", $parentName)->first();

            $tag = Tag::firstOrCreate(
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

            if($tag->wasRecentlyCreated){
                $count++;
            }
        }

        wncms()->cache()->flush(['pages', 'tags']);

        //返回之前的index
        return back()->withMessage(__('word.successfully_created_count', ['count' => $count]));
    }

    public function bulk_delete(Request $request)
    {
        // info($request->all());
        if(is_array($request->model_ids)){
            $count = Tag::whereIn('id', $request->model_ids)->delete();
        }else{
            $count = Tag::whereIn('id', explode(",", $request->model_ids))->delete();
        }

        if($request->is_ajax || $request->isAjax){
            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('{{ modelVariable }}s.index')->withMessage(__('word.successfully_deleted_count', ['count' => $count]));

    }

    public function import_csv(Request $request)
    {
        if ($request->hasFile('csv_file')) {
            Excel::import(new TagImport($request), $request->file('csv_file'));
        }

        //clear cache
        wncms()->cache()->flush(['pages', 'tags']);

        //返回之前的index
        if (session('current_url')) {
            return redirect(session('current_url'))->withInput()->withMessage(__('word.successfully_created'));
        }
        return redirect()->route('tags.index')->withInput()->withMessage(__('word.successfully_created'));
    }

    //! Keywords
    public function show_keyword_index(Request $request)
    {
        $q = Tag::query();

        $selectedType = $type ?? $request->type ?? 'post_category';
        if($selectedType != 'all'){
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

        if (in_array($request->order, Tag::ORDERS)) {
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

        return view('wncms::backend.tags.keywords.index', [
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
        foreach($keywordsToUpdate as $keywordName){
            $tag->keywords()->updateOrCreate(['name' => $keywordName]);
        }

        //get current tag keyword name list array
        $keywordNameList = $tag->keywords()->pluck('name')->toArray();
        
        //remove tag not in name list
        $removingKeywords = array_diff($keywordNameList, $keywordsToUpdate);

        $removedKeywords = $tag->keywords()->whereIn('name', $removingKeywords)->delete();

        // dd(
        //     $request->all(),
        //     $tag->id,
        //     $keywordsToUpdate,
        //     $keywordNameList,
        //     $removingKeywords,
        //     $removedKeywords,
        // );
        return redirect()->route('tags.keywords.index', [
            'type' => $tag->type,
        ])->withMessage(__('word.tag_keywords_are_updated'));
    }

    //! Types
    public function create_type()
    {
        return view('wncms::backend.tags.types.create', [
            'page_title' => wncms_model_word('tag', 'management'),
        ]);
    }

    public function store_type(Request $request)
    {
        $tag = Tag::findOrCreate(__('word.default'), $request->slug);
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

        $parent = Tag::find($formData['parent_id']);
        if(!$parent){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.parent_tag_is_not_found'),
                'reload' => true,
            ]);
        }

        if(empty($request->model_ids)){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.tags_are_not_found'),
                'reload' => true,
            ]);
        }
        
        $tags = Tag::whereIn('id', $request->model_ids)->update([
            'parent_id' => $parent->id,
        ]);

        if($tags){
            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_created'),
                'reload' => true,
            ]);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => __('word.tags_are_not_updated'),
                'reload' => true,
            ]);
        }
    
    }

    public function get_languages(Request $request)
    {
        // info($request->all());
        if(empty($request->model_id)){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.model_id_is_not_found'),
            ]);
        }

        $tag = Tag::find($request->model_id);

        if(!$tag){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.model_is_not_found'),
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_created'),
            'translations' => $tag->getTranslations(),
        ]);
        
    }
}
