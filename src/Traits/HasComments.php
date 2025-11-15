<?php

namespace Wncms\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Polymorphic relation: any model using this trait can have comments.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(wncms()->getModelClass('comment'), 'commentable');
    }

    /**
     * Quick helper to add a comment to this model.
     *
     * @param string $content
     * @param int|null $userId
     * @param string $status
     * @param int|null $parentId
     * @return \Wncms\Models\Comment
     */
    public function addComment(string $content, ?int $userId = null, string $status = 'visible', ?int $parentId = null)
    {
        return $this->comments()->create([
            'content' => $content,
            'user_id' => $userId ?? auth()->id(),
            'status' => $status,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Get total comment count (including replies).
     *
     * @return int
     */
    public function getCommentCount(): int
    {
        return $this->comments()->count();
    }
}
