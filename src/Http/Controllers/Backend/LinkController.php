<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class LinkController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
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
        ]);
        $this->syncBackendMutationWebsites($link);

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
        ]);
        $this->syncBackendMutationWebsites($link);

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

    /**
     * Bulk update link sort and url
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulk_update(Request $request)
    {
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

            foreach ($links as $link) {
                if (($formDataArray['action'] == 'sync')) {
                    if (!empty($link_categories)) {
                        $link->syncTagsWithType($link_categories, 'link_category');
                    }
                    if (!empty($link_tags)) {
                        $link->syncTagsWithType($link_tags, 'link_tag');
                    }
                }

                if (($formDataArray['action'] == 'attach')) {
                    if (!empty($link_categories)) {
                        $link->attachTags($link_categories, 'link_category');
                    }
                    if (!empty($link_tags)) {
                        $link->attachTags($link_tags, 'link_tag');
                    }
                }

                if (($formDataArray['action'] == 'detach')) {
                    if (!empty($link_categories)) {
                        $link->detachTags($link_categories, 'link_category');
                    }
                    if (!empty($link_tags)) {
                        $link->detachTags($link_tags, 'link_tag');
                    }
                }
            }

            wncms()->cache()->flush(['links']);

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
}
