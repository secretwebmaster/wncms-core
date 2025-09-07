<style>
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .comment-list {
        margin-top: 1rem;
    }

    .comment {
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
    }

    .comment .comment-body {
        background: #fff;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        flex-grow: 1;
    }

    /* Replies */
    .children {
        margin-left: 4.5rem;
        margin-top: 0.5rem;
    }

    .comment .children .comment {
        margin-bottom: 1rem;
    }

    .comment .children .comment-body {
        background: #fff;
        border-radius: 0.75rem;
        padding: 0.9rem 1.1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .comment-content {
        line-height: 1.5;
    }

    .comment-actions a {
        font-size: 0.85rem;
        text-decoration: none;
        margin-right: 1rem;
    }

    .comment-actions a:hover {
        text-decoration: underline;
    }
</style>

<div class="row">
    <div class="col-12">

        {{-- Existing Comments --}}
        @if($comments->isEmpty())
            <p class="text-muted">@lang('wncms::word.no_comments')</p>
        @else
            <p class="text-muted">@lang('wncms::word.comment_count', ['count' => $post->getCommentCount()])</p>
            <div class="comment-list">
                @foreach($comments as $comment)
                    @include('wncms::backend.posts.comment-item', ['comment' => $comment])
                @endforeach
            </div>
        @endif

        {{-- Add Comment Form --}}
        <div class="add-comment mt-5">
            <form method="POST" action="{{ route('comments.store') }}">
                @csrf
                <input type="hidden" name="commentable_type" value="{{ get_class($post) }}">
                <input type="hidden" name="commentable_id" value="{{ $post->id }}">

                <div class="d-flex">
                    <div class="me-3">
                        <div class="avatar-circle bg-secondary text-white fw-bold">
                            {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <textarea name="content" rows="3" class="form-control mb-2" placeholder="@lang('wncms::word.write_a_comment')"></textarea>
                        <button type="submit" class="btn btn-dark btn-sm">@lang('wncms::word.add_comment')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
