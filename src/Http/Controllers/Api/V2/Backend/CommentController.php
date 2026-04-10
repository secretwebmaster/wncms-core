<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends ResourceController
{
    public function updateViaPost(Request $request, int|string $id)
    {
        return $this->update($request, 'comments', $id);
    }

    public function destroyViaPost(Request $request, int|string $id)
    {
        return $this->destroy($request, 'comments', $id);
    }

    public function searchUsers(Request $request)
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

        $users = $query->orderBy('username')->limit(50)->get()->map(fn ($user) => [
            'id' => (int) $user->id,
            'username' => (string) ($user->username ?? ''),
            'email' => (string) ($user->email ?? ''),
        ])->values()->all();

        return $this->ok([
            'users' => $users,
        ]);
    }

    public function store(Request $request, string $resource)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (!$config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['store'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (!$modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $validated = $this->validateCommentPayload($request, $modelClass);
            $comment = $modelClass::create([
                'commentable_type' => $validated['commentable_type'],
                'commentable_id' => $validated['commentable_id'],
                'content' => $validated['content'],
                'user_id' => array_key_exists('user_id', $validated) ? $validated['user_id'] : auth()->id(),
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'] ?? 'visible',
            ]);

            $this->flushCommentCache();

            return $this->ok($this->transformComment($comment->fresh(['user', 'children', 'children.user'])), 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function update(Request $request, string $resource, int|string $id)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (!$config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['update'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (!$modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $comment = $modelClass::query()->find($id);
            if (!$comment) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $validated = $this->validateCommentPayload($request, $modelClass, (int) $comment->id, true);

            $payload = [
                'content' => $validated['content'],
                'user_id' => $validated['user_id'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'] ?? $comment->status,
            ];

            if (!empty($validated['created_at'])) {
                $payload['created_at'] = $validated['created_at'];
            }

            $comment->update($payload);

            $this->flushCommentCache();

            return $this->ok($this->transformComment($comment->fresh(['user', 'children', 'children.user'])), 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function destroy(Request $request, string $resource, int|string $id)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (!$config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['destroy'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (!$modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $comment = $modelClass::query()->find($id);
            if (!$comment) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $comment->delete();
            $this->flushCommentCache();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    protected function validateCommentPayload(Request $request, string $modelClass, ?int $commentId = null, bool $isUpdate = false): array
    {
        return $request->validate(
            [
                'commentable_type' => [$isUpdate ? 'sometimes' : 'required', 'string'],
                'commentable_id' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
                'content' => 'required|string|max:2000',
                'user_id' => 'nullable|exists:users,id',
                'created_at' => 'nullable|date',
                'status' => ['nullable', Rule::in($modelClass::STATUSES)],
                'parent_id' => [
                    'nullable',
                    'exists:' . (new $modelClass)->getTable() . ',id',
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

    protected function transformComment($comment): array
    {
        return [
            'id' => (int) $comment->id,
            'commentable_type' => (string) $comment->commentable_type,
            'commentable_id' => (int) $comment->commentable_id,
            'content' => (string) $comment->content,
            'status' => (string) $comment->status,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'created_at' => optional($comment->created_at)?->toISOString(),
            'author' => $comment->user ? [
                'id' => (int) $comment->user->id,
                'username' => (string) ($comment->user->username ?? ''),
                'email' => (string) ($comment->user->email ?? ''),
            ] : null,
            'children' => $comment->relationLoaded('children')
                ? $comment->children->map(fn ($child) => $this->transformComment($child))->values()->all()
                : [],
        ];
    }

    protected function flushCommentCache(): void
    {
        wncms()->cache()->tags(['comments', 'posts'])->flush();
    }
}
