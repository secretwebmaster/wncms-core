<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class CommentController extends BackendController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:' . (new $this->modelClass)->getTable() . ',id',
        ]);

        $comment = $this->modelClass::create([
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'],
            'content' => $validated['content'],
            'user_id' => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => 'visible',
        ]);

        return back()->withMessage(__('wncms::word.successfully_created'));
    }

    public function destroy($id)
    {
        $comment = $this->modelClass::findOrFail($id);
        $comment->delete();

        return redirect()->back()->with('active_tab', 'comments')->withMessage(__('wncms::word.successfully_deleted'));
    }
}
