<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdvertisementController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if (in_array($request->status, $this->modelClass::STATUSES)) {
            $q->where('status', $request->status);
        }

        if (in_array($request->position, $this->modelClass::POSITIONS)) {
            $q->where('position', $request->position);
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
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

        if (in_array($request->sort, $this->modelClass::SORTS)) {
            $q->orderBy($request->sort, in_array($request->direction, ['asc', 'desc']) ? $request->direction : 'desc');
        }

        $q->with(['media']);

        $q->orderBy('id', 'desc');

        $advertisements = $q->paginate($request->page_size ?? 20);

        return $this->view('backend.advertisements.index', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisements' => $advertisements,
            'sorts' => $this->modelClass::SORTS,
            'statuses' => $this->modelClass::STATUSES,
            'positions' => $this->modelClass::POSITIONS,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $advertisement = $this->modelClass::find($id);
            if (!$advertisement) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $advertisement = new $this->modelClass;
        }

        $advertisementTags =  wncms()->getModel('tag')::where('type', 'advertisement_tag')->pluck('name')->toArray();
        $websites = wncms()->website()->getList();

        return $this->view('backend.advertisements.create', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisement' => $advertisement ?? new $this->modelClass,
            'advertisement_tags' => $advertisementTags,
            'positions' => $this->modelClass::POSITIONS,
            'websites' => $websites,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        if (gss('multi_website')) {
            $websiteId = $request->website_id;
        } else {
            $websiteId = wncms()->website()->get()?->id;
            if (!$websiteId) {
                return back()->withMessage(__('wncms::word.website_not_found'));
            }
        }

        $advertisement = $this->modelClass::create([
            'website_id' => $websiteId,
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
            'sort' => $request->sort,
            'contact' => $request->contact,
        ]);

        if (gss('multi_website')) {
            $websiteId = $request->website_id;
            if ($websiteId) {
                $advertisement->bindWebsites($websiteId);
            }
        }

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

        $this->flush();

        return redirect()->route('advertisements.edit', [
            'id' => $advertisement,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $advertisement = $this->modelClass::find($id);
        if (!$advertisement) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $advertisementTags = wncms()->getModel('tag')::where('type', 'advertisement_tag')->pluck('name')->toArray();
        $websites = wncms()->website()->getList();

        return $this->view('backend.advertisements.edit', [
            'page_title' => wncms_model_word('advertisement', 'management'),
            'advertisement' => $advertisement,
            'advertisement_tags' => $advertisementTags,
            'positions' => $this->modelClass::POSITIONS,
            'websites' => $websites,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());

        // TODO: remove single model binding
        if (gss('multi_website')) {
            $websiteId = $request->website_id;
        } else {
            $websiteId = wncms()->website()->get()?->id;
            if (!$websiteId) {
                return back()->withMessage(__('wncms::word.website_not_found'));
            }
        }

        $advertisement = $this->modelClass::find($id);
        if (!$advertisement) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.advertisement')]));
        }

        $advertisement->update([
            'website_id' => $websiteId,
            'status' => $request->status,
            'expired_at' => $request->expired_at,
            'name' => $request->name,
            'type' => $request->type,
            'position' => $request->position,
            'sort' => $request->sort,
            'cta_text' => $request->cta_text,
            'url' => $request->url,
            'cta_text_2' => $request->cta_text_2,
            'url_2' => $request->url_2,
            'remark' => $request->remark,
            'text_color' => $request->text_color,
            'background_color' => $request->background_color,
            'code' => $request->code,
            'style' => $request->style,
            'contact' => $request->contact,
        ]);

        // multisite
        if (gss('multi_website')) {
            $websiteId = $request->website_id;
            if ($websiteId) {
                $advertisement->bindWebsites($websiteId);
            }
        }

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

        $this->flush();

        return redirect()->route('advertisements.edit', [
            'id' => $advertisement,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
