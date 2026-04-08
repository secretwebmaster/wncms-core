<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends BackendController
{
    /**
     * Store a newly created comment.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $this->validateCommentPayload($request);

        $this->modelClass::create([
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'],
            'content' => $validated['content'],
            'user_id' => array_key_exists('user_id', $validated) ? $validated['user_id'] : auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $validated['status'] ?? 'visible',
        ]);

        $this->flush();

        return back()->with('active_tab', 'comments')->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Update the specified comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $comment = $this->modelClass::findOrFail($id);

        $validated = $this->validateCommentPayload($request, $comment->id);

        $payload = [
            'content' => $validated['content'],
            'user_id' => $validated['user_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $validated['status'] ?? $comment->status,
        ];
        if (!empty($validated['created_at'])) {
            $payload['created_at'] = Carbon::parse($validated['created_at']);
        }

        $comment->update($payload);

        $this->flush();

        return back()->with('active_tab', 'comments')->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Delete the specified comment.
     *
     * @param  int|string  $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $comment = $this->modelClass::findOrFail($id);
        $comment->delete();

        $this->flush();

        return redirect()->back()->with('active_tab', 'comments')->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Search users for comment author assignment.
     *
     * Non-admin users can only assign themselves, while admins can search by
     * username or email and receive at most 50 results per request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $userModelClass = wncms()->getModelClass('user');
        $query = $userModelClass::query()->select(['id', 'username', 'email']);

        if (!isAdmin()) {
            $query->where('id', auth()->id());
        } elseif ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('username', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        $users = $query->orderBy('username')
            ->limit(50)
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Validate the incoming comment payload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $commentId
     *
     * @return array
     */
    protected function validateCommentPayload(Request $request, ?int $commentId = null): array
    {
        return $request->validate(
            [
                'commentable_type' => 'required|string',
                'commentable_id' => 'required|integer',
                'content' => 'required|string|max:2000',
                'user_id' => 'nullable|exists:users,id',
                'created_at' => 'nullable|date',
                'status' => ['nullable', Rule::in($this->modelClass::STATUSES)],
                'parent_id' => [
                    'nullable',
                    'exists:' . (new $this->modelClass)->getTable() . ',id',
                    Rule::notIn(array_filter([$commentId])),
                ],
            ],
            [
                'content.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.content')]),
                'content.max' => __('wncms::word.field_should_not_exceed', ['field_name' => __('wncms::word.content'), 'value' => '2000']),
                'created_at.date' => __('wncms::word.invalid_request'),
            ]
        );
    }
}
