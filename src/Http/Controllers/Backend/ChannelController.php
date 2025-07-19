<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class ChannelController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $q->withCount('clicks');

        $channels = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.channels.index', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channels' => $channels,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $channel = $this->modelClass::find($id);
            if (!$channel) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $channel = new $this->modelClass;
        }

        return $this->view('backend.channels.create', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channel' => $channel,
        ]);
    }

    public function store(Request $request)
    {
        // check if slug exists
        if ($request->slug) {
            $slugExists = $this->modelClass::where('slug', $request->slug)->exists();
            if ($slugExists) {
                return back()->withInput()->withErrors(['message' => __('wncms::word.channel_exists')]);
            } else {
                $slug = $request->slug;
            }
        } else {
            $slug = wncms()->getUniqueSlug('channels');
        }

        $channel = $this->modelClass::create([
            'name' => $request->name,
            'slug' => $slug,
            'contact' => $request->contact,
            'remark' => $request->remark,
        ]);

        $this->flush();

        return redirect()->route('channels.edit', [
            'channel' => $channel,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $channel = $this->modelClass::find($id);
        if (!$channel) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.channels.edit', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, $id)
    {
        $channel = $this->modelClass::find($id);
        if (!$channel) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        // check if slug exists
        if ($request->slug) {
            $slugExists = $this->modelClass::where('slug', $request->slug)->where('id', '!=', $channel->id)->exists();
            if ($slugExists) {
                return back()->withInput()->withErrors(['message' => __('wncms::word.channel_exists')]);
            } else {
                $slug = $request->slug;
            }
        } else {
            $slug = wncms()->getUniqueSlug('channels');
        }

        $channel->update([
            'name' => $request->name,
            'slug' => $slug,
            'contact' => $request->contact,
            'remark' => $request->remark,
        ]);

        $this->flush();

        return redirect()->route('channels.edit', [
            'channel' => $channel,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
