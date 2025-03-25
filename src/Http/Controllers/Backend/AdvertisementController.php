<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Advertisement;
use Wncms\Models\Tag;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $q = Advertisement::query();

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

        if (in_array($request->status, Advertisement::STATUSES)) {
            $q->where('status', $request->status);
        }

        if (in_array($request->position, Advertisement::POSITIONS)) {
            $q->where('position', $request->position);
        }

        if ($request->keyword) {
            $q->where(function($subq) use($request){
                $subq->orWhere('id', $request->keyword)
                ->orWhere('cta_text', 'like', "%" . $request->keyword . "%")
                ->orWhere('url', 'like', "%" . $request->keyword . "%")
                ->orWhere('cta_text_2', 'like', "%" . $request->keyword . "%")
                ->orWhere('url_2', 'like', "%" . $request->keyword . "%")
                ->orWhere('remark', 'like', "%" . $request->keyword . "%")
                ->orWhere('code', 'like', "%" . $request->keyword . "%")
                ->orWhereRaw("JSON_EXTRACT(name, '$.*') LIKE '%$request->keyword%'");
            });

        }

        if (in_array($request->order, Advertisement::ORDERS)) {
            $q->orderBy($request->order, in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc');
        }

        $q->with(['media', 'website']);

        if($request->show_click){
            $q->withCount(['views as click_count' => function($subq){
                $subq->where('collection', 'click');
            }]);
        }
        
        if($request->show_view){
            $q->withCount(['views as view_count' => function($subq){
                $subq->where('collection', 'view');
            }]);
        }
        
        $advertisements = $q->paginate();
        $websites = wncms()->website()->getList();
        return view('wncms::backend.advertisements.index', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisements' => $advertisements,
            'websites' => $websites,
            'orders' => Advertisement::ORDERS,
            'statuses' => Advertisement::STATUSES,
            'positions' => Advertisement::POSITIONS,
        ]);
    }

    public function create(Advertisement $advertisement = null)
    {
        $advertisement_tags = Tag::where('type', 'advertisement_tag')->pluck('name')->toArray();
        $websites = wncms()->website()->getList();

        return view('wncms::backend.advertisements.create', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisement' => $advertisement ??= new Advertisement,
            'advertisement_tags' => $advertisement_tags,
            'positions' => Advertisement::POSITIONS,
            'websites' => $websites,
            'types' => Advertisement::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $advertisement = Advertisement::create([
            'website_id' => $request->website_id,
            'status' => $request->status,
            'expired_at' => $request->expired_at,
            'name' => $request->name,
            'type' => $request->type,
            'position' => $request->position,
            'cta_text' => $request->cta_text,
            'url' => $request->url,
            'cta_text_2' => $request->cta_text_2,
            'url_2' => $request->url_2,
            'remark' => $request->remark,
            'text_color' => $request->text_color,
            'background_color' => $request->background_color,
            'code' => $request->code,
            'style' => $request->style,
            'order' => $request->order,
        ]);

         //thumbnail
         if (!empty($request->advertisement_thumbnail_remove)) {
            $advertisement->clearMediaCollection('advertisement_thumbnail');
        }

        if (!empty($request->advertisement_thumbnail)) {
            $advertisement->addMediaFromRequest('advertisement_thumbnail')->toMediaCollection('advertisement_thumbnail');
        }

        if (!empty($request->advertisement_thumbnail_clone_id)) {
            $mediaToClone = Media::find($request->advertisement_thumbnail_clone_id);
            if ($mediaToClone) {
                $mediaToClone->copy($advertisement, 'advertisement_thumbnail');
            }
        }

        // $advertisement->localizeImages();
        $advertisement->syncTagsFromTagify($request->advertisement_tags, 'advertisement_tag');

        wncms()->cache()->tags(['advertisements'])->flush();

        return redirect()->route('advertisements.edit', [
            'advertisement' => $advertisement,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Advertisement $advertisement)
    {
        $advertisement_tags = Tag::where('type', 'advertisement_tag')->pluck('name')->toArray();
        $websites = wncms()->website()->getList();
        return view('wncms::backend.advertisements.edit', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisement' => $advertisement,
            'advertisement_tags' => $advertisement_tags,
            'positions' => Advertisement::POSITIONS,
            'websites' => $websites,
            'types' => Advertisement::TYPES,
        ]);
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        // dd($request->all());
        $advertisement->update([
            'website_id' => $request->website_id,
            'status' => $request->status,
            'expired_at' => $request->expired_at,
            'name' => $request->name,
            'type' => $request->type,
            'position' => $request->position,
            'cta_text' => $request->cta_text,
            'url' => $request->url,
            'cta_text_2' => $request->cta_text_2,
            'url_2' => $request->url_2,
            'remark' => $request->remark,
            'text_color' => $request->text_color,
            'background_color' => $request->background_color,
            'code' => $request->code,
            'style' => $request->style,
        ]);

        // thumbnail
         if (!empty($request->advertisement_thumbnail_remove)) {
            $advertisement->clearMediaCollection('advertisement_thumbnail');
        }

        if (!empty($request->advertisement_thumbnail)) {
            $advertisement->addMediaFromRequest('advertisement_thumbnail')->toMediaCollection('advertisement_thumbnail');
        }

        if (!empty($request->advertisement_thumbnail_clone_id)) {
            $mediaToClone = Media::find($request->advertisement_thumbnail_clone_id);
            if ($mediaToClone) {
                $mediaToClone->copy($advertisement, 'advertisement_thumbnail');
            }
        }

        // $advertisement->localizeImages();
        $advertisement->syncTagsFromTagify($request->advertisement_tags, 'advertisement_tag');


        wncms()->cache()->tags(['advertisements'])->flush();
        
        return redirect()->route('advertisements.edit', [
            'advertisement' => $advertisement,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Advertisement $advertisement)
    {
        $advertisement->delete();
        return redirect()->route('advertisements.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }
        
        Advertisement::whereIn('id', $modelIds)->delete();
        return redirect()->route('advertisements.index')->withMessage(__('wncms::word.successfully_deleted'));
    }
}
