<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Http\Requests\BannerFormRequest;
use Wncms\Models\Banner;
use Wncms\Models\Website;
use Wncms\Services\BannerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    protected $relatedCacheKeys = ['banners', 'pages'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = Banner::query();

        if (!isAdmin()) {
            $q->whereHas('website', function ($subq) {
                $subq->whereHas('users', function ($subsubq) {
                    $subsubq->where('users.id', auth()->id());
                });
            });
        }

        $selectedWebsiteId = $request->website ?? session('selected_website_id');
        if($selectedWebsiteId){
            $q->whereHas('website', function ($subq) use ($selectedWebsiteId) {
                $subq->where('websites.id', $selectedWebsiteId);
            });
        }elseif(!$request->has('website')){
            $websiteId = wncms()->website()->get()?->id;
            $q->whereHas('website', function ($subq) use ($websiteId) {
                $subq->where('websites.id', $websiteId);
            });
        }

        if ($request->keyword) {
            $q->where('remark', 'like', "%$request->keyword%")->orWhere('url', 'like', "%$request->keyword%");
        }

        if ($request->website) {
            $q->whereHas('website', function ($subq) use ($request) {
                $subq->where('id', $request->website);
            });
        }

        $q->orderBy('order', 'desc');
        $q->orderBy('id', 'desc');
        $q->with(['media']);

        $banners = $q->paginate($request->page_size ?? 20);

        $websites = wncms()->website()->getList();

        return view('backend.banners.index', [
            'banners' => $banners,
            'websites' => $websites,
            'orders' => Banner::ORDERS,
            'page_title' => __('word.banner_management'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Banner $banner = null)
    {
        $websites = wncms()->website()->getList();
        return view('backend.banners.create', [
            'page_title' => __('word.banner_management'),
            'positions' => Banner::POSITIONS,
            'statuses' => Banner::STATUSES,
            'websites' => $websites,
            'banner' => $banner ??= new Banner,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BannerFormRequest $request)
    {
        // dd($request->all());
        if(empty($request->website_ids)){
            return back()->withInput()->withErrors(['message' => __('word.website_is_not_selected')]);
        }
        if(empty($request->banner_thumbnail)){
            return back()->withInput()->withErrors(['message' => __('word.please_upload_an_image')]);
        }
        $websites = wncms()->website()->getList($request->website_ids);
        
        foreach ($websites as $website) {
            $banner = $website->banners()->create($request->validated());

            //handle image
            if ($request->hasfile('banner_thumbnail')) {
                $banner->addMediaFromRequest('banner_thumbnail')->preservingOriginal()->toMediaCollection('banner_thumbnail');
            }
        }
        wncms()->cache()->flush($this->relatedCacheKeys);
        return redirect()->route('banners.index');
    }

    public function bulk_clone(Request $request)
    {
        // info($request->all());
        $banners = Banner::whereIn('id', $request->model_ids)->get();
        $website = Website::find($request->website_id);

        if (!$website || $banners->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'message' => __('word.failed_to_update'),
            ]);
        }

        $count = 0;
        foreach ($banners as $banner) {

            $new_banner = $banner->replicate();
            $new_banner->website_id = $website->id;
            $clone = $new_banner->save();

            //handle media
            $banner_thumbnail = $banner->getMedia('banner_thumbnail')->first();
            if ($banner_thumbnail) {
                $banner_thumbnail->copy($new_banner, 'banner_thumbnail');
            }

            if ($clone) $count++;
        }

        if ($count == 0) {
            cache()->flush();
            return response()->json([
                'status' => 'success',
                'message' => __('word.nothing_created'),
            ]);
        }

        if ($count < $banners->count()) {
            cache()->flush();
            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_created_some'),
            ]);
        }

        wncms()->cache()->flush($this->relatedCacheKeys);

        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_created_all'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Wncms\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Wncms\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        $websites = wncms()->website()->getList();
        return view('backend.banners.edit', [
            'page_title' => __('word.banner_management'),
            'banner' => $banner,
            'positions' => Banner::POSITIONS,
            'statuses' => Banner::STATUSES,
            'websites' => $websites,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(BannerFormRequest $request, Banner $banner)
    {
        // dd($request->all());
        $banner->update($request->validated());

        if ($request->image_remove) {
            $banner->clearMediaCollection('banner_thumbnail');
        }

        if ($request->hasfile('banner_thumbnail')) {
            $banner_thumbnails[] = $banner->addMediaFromRequest('banner_thumbnail')->preservingOriginal()->toMediaCollection('banner_thumbnail');
        }

        wncms()->cache()->tags(['banners', 'pages'])->flush();
        return redirect()->route('banners.edit', $banner)->with(['message' => __('word.successfully_updated')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Wncms\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();
        wncms()->cache()->tags(['banners'])->flush();
        wncms()->cache()->tags('pages')->flush();
        return back()->with([
            'status' => 'success',
            'message' => __('word.successfully_deleted'),
        ]);
    }

    public function bulk_delete(Request $request)
    {
        // info($request->all());
        $count = (new BannerService)->bulk_delete($request->model_ids);

        //clear cache
        wncms()->cache()->tags(['banners', 'pages'])->flush();

        if ($count == count($request->model_ids)) {
            return response()->json(['status' => 'success', 'message' => __('word.successfully_deleted_all')]);
        } elseif ($count > 0) {
            return response()->json(['status' => 'success', 'message' => __('word.successfully_deleted_some')]);
        } else {
            return response()->json(['status' => 'fail', 'message' => __('word.failed_to_delete')]);
        }
    }
}
