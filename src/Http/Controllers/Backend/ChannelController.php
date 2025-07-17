<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Channel;
use Illuminate\Http\Request;

class ChannelController extends BackendController
{
    public function getModelClass(): string
    {
        return config('wncms.models.channel', \Wncms\Models\Channel::class);
    }

    public function index(Request $request)
    {
        $q = Channel::query();

        $q->withCount('clicks');
        
        $channels = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.channels.index', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channels' => $channels,
        ]);
    }

    public function create(?Channel $channel)
    {
        $channel ??= new Channel;

        return view('wncms::backend.channels.create', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channel' => $channel,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        // check if slug exists
        if($request->slug){
            $slugExists = Channel::where('slug', $request->slug)->exists();
            if($slugExists){
                return back()->withInput()->withErrors(['message' => __('wncms::word.channel_exists')]);
            }else{
                $slug = $request->slug;
            }
        }else{
            $slug = wncms()->getUniqueSlug('channels');
        }

        $channel = Channel::create([
            'name' => $request->name,
            'slug' => $slug,
            'contact' => $request->contact,
            'remark' => $request->remark,
        ]);

        wncms()->cache()->flush(['channels']);

        return redirect()->route('channels.edit', [
            'channel' => $channel,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Channel $channel)
    {
        return view('wncms::backend.channels.edit', [
            'page_title' =>  wncms_model_word('channel', 'management'),
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, Channel $channel)
    {
        // dd($request->all());
        // check if slug exists
        if($request->slug){
            $slugExists = Channel::where('slug', $request->slug)->where('id', '!=', $channel->id)->exists();
            if($slugExists){
                return back()->withInput()->withErrors(['message' => __('wncms::word.channel_exists')]);
            }else{
                $slug = $request->slug;
            }
        }else{
            $slug = wncms()->getUniqueSlug('channels');
        }
        
        $channel->update([
            'name' => $request->name,
            'slug' => $slug,
            'contact' => $request->contact,
            'remark' => $request->remark,
        ]);

        wncms()->cache()->flush(['channels']);
        
        return redirect()->route('channels.edit', [
            'channel' => $channel,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();
        return redirect()->route('channels.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = Channel::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('channels.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
