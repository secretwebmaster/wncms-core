<?php

namespace Wncms\Http\Controllers\Frontend;


use Wncms\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends FrontendController
{
    public function store(Request $request)
    {
        if(gto('disable_comment')) {
            return back()->with('error', __('wncms::word.comment_disabled'));
        }

        $modelClass = $request->commentable_type;
        if(!class_exists($modelClass)) {
            $status = 'error';
            $message = __('wncms::word.model_type_not_found');
        }

        $model = $modelClass::find($request->commentable_id);
        if(!$model) {
            $status = 'error';
            $message = __('wncms::word.model_not_found');
        }

        // dd('saving user comment', $request->all());
        if(auth()->check()){
            // get requiement to save comment
            $creditRequirement = gto('credit_requiement_to_comment', 0);
            $userCredit = auth()->user()->credits()->where('type', 'balance')->first()->amount ?? 0;

            if($userCredit < $creditRequirement) {
                $status = 'error';
                $message = __('wncms::word.insufficient_credits');
            }

            $userId = auth()->id();
        }else{
            if(gto('allow_guest_comment')){
                $userId = null;
            }else{
                return redirect()->back()->withFragment('comments')->with('error', __('wncms::word.login_first'));
            }
        }

        $content = $this->filter($request->content);

        // dd($request->all());
        $comment = Comment::create([
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'user_id' => $userId,
            'parent_id' => $request->parent_id,
            'content' => $content,
        ]);

        wncms()->cache()->tags('posts')->flush();

        $status = 'success';
        $message = __('wncms::word.comment_saved');

        if($request->ajax()) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'comment' => $comment,
            ]);
        }

        return back()->with($status, $message);
    }

    protected function filter($content)
    {
        // remote script
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);

        return $content;
    }
}
