<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->locale ?? app()->getLocale();
        // info($request->all());
        $tags = Tag::where('type', $request->type)
            ->whereNull('parent_id')
            ->with('children', 'children.children')
            ->orderBy('order_column', 'desc')
            ->with('translations')
            ->get()
            ->map(function ($tag) use($locale){
                $tag->name = $tag->getTranslation('name', $locale);
                return $tag;
            });

        return $tags;
    }

    public function exist(Request $request)
    {
        // info($request->all());
        $tagIds = Tag::whereIn('id', $request->tagIds ?? [])->pluck('id')->toArray();
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetched_data'),
            'ids' => $tagIds,
        ]);
    }
}
