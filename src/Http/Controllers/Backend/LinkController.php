<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Link;
use Illuminate\Http\Request;
use Wncms\Models\Tag;

class LinkController extends BackendController
{
    public function index(Request $request)
    {
        $q = Link::query();

        if($request->keyword){
            $q->where(function($subq) use ($request){
                $subq->where('name', 'like', '%'.$request->keyword.'%')
                ->orWhere('url', 'like', '%'.$request->keyword.'%')
                ->orWhere('remark', 'like', '%'.$request->keyword.'%')
                ->orWhere('description', 'like', '%'.$request->keyword.'%');
            });
        }

        if($request->link_category_id){
            $q->whereHas('tags', function($q) use ($request){
                $q->where('type', 'link_category')->where(function($q) use ($request){
                    $q->where('tags.id', $request->link_category_id)->orWhere('tags.name', $request->link_category_id);
                });
            });
        }

        if($request->status){
            $q->where('status', $request->status);
        }

        $q->orderBy('is_pinned', 'desc');
        $q->orderBy('order', 'desc');
        $q->orderBy('id', 'desc');

        $links = $q->paginate($request->page_size ?? 100);

        $parentLinkCategories = Tag::where('type', 'link_category')->whereNull('parent_id')->get()->unique();
        // dd($parentLinkCategories);

        return view('wncms::backend.links.index', [
            'page_title' =>  wncms_model_word('link', 'management'),
            'links' => $links,
            'statuses' => Link::STATUSES,
            'parentLinkCategories' => $parentLinkCategories,
        ]);
    }

    public function create(Link $link = null)
    {
        $link ??= new Link;

        return view('wncms::backend.links.create', [
            'page_title' =>  wncms_model_word('link', 'management'),
            'link' => $link,
            'statuses' => Link::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $uid = wncms()->getUniqueSlug('links', 'slug', 8, 'lower');

        $link = Link::create([
            'status' => $request->status,

            'tracking_code' => $request->tracking_code ?? $uid,
            'slug' => $request->slug ?? $uid,

            'name' => $request->name,
            'url' => $request->url,
            'slogan' => $request->slogan,
            'description' => $request->description,
            'external_thumbnail' => $request->external_thumbnail,
            'remark' => $request->remark,
            'order' => $request->order,
            'color' => $request->color,
            'background' => $request->background,
            'is_pinned' => $request->is_pinned ? true : false,
            'is_recommended' => $request->is_recommended ? true : false,
            'expired_at' => $request->expired_at,
            'hit_at' => $request->hit_at,

            'clicks' => $request->clicks,
            'contact' => $request->contact,
        ]);

        //thumbnail
        if (!empty($request->link_thumbnail_remove)) {
            $link->clearMediaCollection('link_thumbnail');
        }

        if (!empty($request->link_thumbnail)) {
            $link->addMediaFromRequest('link_thumbnail')->toMediaCollection('link_thumbnail');
        }

        //tags
        $link->syncTagsFromTagify($request->link_categories, 'link_category');
        $link->syncTagsFromTagify($request->link_tags, 'link_tag');

        wncms()->cache()->flush(['links']);

        return redirect()->route('links.edit', [
            'link' => $link,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Link $link)
    {
        return view('wncms::backend.links.edit', [
            'page_title' =>  wncms_model_word('link', 'management'),
            'link' => $link,
            'statuses' => Link::STATUSES,
        ]);
    }

    public function update(Request $request, Link $link)
    {
        // dd($request->all());

        $link->update([
            'status' => $request->status,

            'tracking_code' => $request->tracking_code ?? $link->tracking_code,
            'slug' => $request->slug ?? $link->slug,

            'name' => $request->name,
            'url' => $request->url,
            'slogan' => $request->slogan,
            'description' => $request->description,
            'external_thumbnail' => $request->external_thumbnail,
            'remark' => $request->remark,
            'order' => $request->order,
            'color' => $request->color,
            'background' => $request->background,
            'is_pinned' => $request->is_pinned ? true : false,
            'is_recommended' => $request->is_recommended ? true : false,
            'expired_at' => $request->expired_at,
            'hit_at' => $request->hit_at,

            'clicks' => $request->clicks,
            'contact' => $request->contact,
        ]);

        //thumbnail
        if (!empty($request->link_thumbnail_remove)) {
            $link->clearMediaCollection('link_thumbnail');
        }

        if (!empty($request->link_thumbnail)) {
            $link->addMediaFromRequest('link_thumbnail')->toMediaCollection('link_thumbnail');
        }

        // icon
        if (!empty($request->link_icon_remove)) {
            $link->clearMediaCollection('link_icon');
        }

        if (!empty($request->link_icon)) {
            $link->addMediaFromRequest('link_icon')->toMediaCollection('link_icon');
        }

        //tags
        $link->syncTagsFromTagify($request->link_categories, 'link_category');
        $link->syncTagsFromTagify($request->link_tags, 'link_tag');

        wncms()->cache()->flush(['links']);

        return redirect()->route('links.edit', [
            'link' => $link,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Link $link)
    {
        $link->delete();
        return redirect()->route('links.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if (!is_array($request->model_ids)) {
            $modelIds = explode(",", $request->model_ids);
        } else {
            $modelIds = $request->model_ids;
        }

        $count = Link::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('links.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
