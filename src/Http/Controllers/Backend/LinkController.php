<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class LinkController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($request->order === 'views_yesterday') {
            $yesterday = now()->subDay()->toDateString();
            $q->leftJoin('total_views as tv_y', function ($join) use ($yesterday) {
                $join->on('links.id', '=', 'tv_y.link_id')
                    ->where('tv_y.date', $yesterday);
            });
            $q->orderByDesc('tv_y.total');
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
                $keyword = str_replace('@', '', $request->keyword);
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
        // $q->orderBy('order', 'desc');
        $q->orderBy('links.id', 'desc');

        $links = $q->paginate($request->page_size ?? 100);

        $parentLinkCategories = wncms()->getModelClass('tag')::where('type', 'link_category')->whereNull('parent_id')->get()->unique();

        return $this->view('backend.links.index', [
            'page_title' =>  wncms_model_word('link', 'management'),
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

        return $this->view('backend.links.create', [
            'page_title' =>  wncms_model_word('link', 'management'),
            'link' => $link,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $uid = wncms()->getUniqueSlug('links', 'slug', 8, 'lower');

        $link = $this->modelClass::create([
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
            'id' => $link->id,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $link = $this->modelClass::find($id);
        if (!$link) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.link')]));
        }

        return $this->view('backend.links.edit', [
            'page_title' => wncms_model_word('link', 'management'),
            'link' => $link,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $link = $this->modelClass::find($id);
        if (!$link) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.link')]));
        }

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
            'is_pinned' => (bool) $request->is_pinned,
            'is_recommended' => (bool) $request->is_recommended,
            'expired_at' => $request->expired_at,
            'hit_at' => $request->hit_at,
            'clicks' => $request->clicks,
            'contact' => $request->contact,
        ]);

        // thumbnail
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

        // tags
        if (method_exists($link, 'syncTagsFromTagify')) {
            $link->syncTagsFromTagify($request->link_categories, 'link_category');
            $link->syncTagsFromTagify($request->link_tags, 'link_tag');
        }

        wncms()->cache()->flush(['links']);

        return redirect()->route('links.edit', ['id' => $link->id])
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function bulk_update_order(Request $request)
    {
        $count = 0;
        foreach ($request->data as $linkData) {
            $link = $this->modelClass::find($linkData['id']);
            if ($link && $link->order != $linkData['order']) {
                $link->update(['order' => $linkData['order']]);
                $count++;
            } else {
                info("link not found");
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated_count', ['count' => $count]),
            'data' => [
                'count' => $count
            ]
        ]);
    }
}
