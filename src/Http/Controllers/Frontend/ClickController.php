<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Jobs\RecordClicks;
use Wncms\Models\Channel;
use Illuminate\Http\Request;
use Wncms\Http\Controllers\Frontend\FrontendController;

class ClickController extends FrontendController
{
    public function record(Request $request)
    {
        info('click record');
        info($request->all());
    
        // Try to load the polymorphic target model
        $clickableModel = null;
        if ($request->filled('clickable_id') && $request->filled('clickable_type')) {
            $modelClass = $request->clickable_type;
    
            if (class_exists($modelClass)) {
                $clickableModel = $modelClass::find($request->clickable_id);
            }
        }
    
        if (!$clickableModel) {
            return response()->json(['error' => 'Target model not found'], 404);
        }
    
        $channel = Channel::where('slug', $request->channel)->first();
    
        // TODO: Optionally add logic to prevent duplicate clicks here
    
        // Dispatch click to queue
        RecordClicks::dispatch([
            'clickable_id' => $request->clickable_id,
            'clickable_type' => $request->clickable_type,
            'channel_id' => $channel?->id,
            'name' => $request->name,
            'value' => $request->value,
            'ip' => $request->ip(),
            'referer' => $request->referer,
            'parameters' => $request->parameters,
        ]);
    
        return response()->json(['success' => true, 'message' => 'Click recorded']);
    }
    
}