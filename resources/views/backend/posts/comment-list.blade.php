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

    .comment-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .comment-toolbar-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .comment-toolbar-form .form-select {
        min-width: 190px;
    }

    .comment-inline-editor {
        border: 1px solid #eef1f5;
        border-radius: 0.9rem;
        background: #fcfcfd;
        padding: 1rem;
    }

    .comment-reply-box {
        margin-left: 1.5rem;
        padding: 1rem 1rem 1rem 2.5rem;
        border-radius: 0.9rem;
        background: #f8fafc;
        position: relative;
    }

    .comment-reply-box::before {
        content: '\f064';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        left: 1rem;
        top: 1.1rem;
        color: #7e8299;
        font-size: 0.95rem;
    }

    .comment-user-dropdown.tagify__dropdown {
        max-height: 320px;
        overflow-y: auto;
        border-radius: 0.475rem;
        border: 1px solid #d8dee9;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }

    .comment-user-dropdown .tagify__dropdown__item {
        padding: 0.4rem 0.6rem;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .comment-user-dropdown .tagify__dropdown__item:hover,
    .comment-user-dropdown .tagify__dropdown__item--active {
        background: #1e1e2d;
        color: #fff;
    }

    .comment-user-dropdown .tagify__dropdown__item strong {
        font-size: 0.9rem;
        line-height: 1.2;
    }

    .comment-user-dropdown .tagify__dropdown__item span {
        font-size: 0.78rem;
        color: #7e8299;
    }

    .comment-user-dropdown .tagify__dropdown__item:hover span,
    .comment-user-dropdown .tagify__dropdown__item--active span {
        color: rgba(255, 255, 255, 0.72);
    }

    .comment-user-selector .tagify {
        width: 100%;
        min-height: calc(1.5em + 0.85rem + 2px);
        height: calc(1.5em + 0.85rem + 2px);
        border: 1px solid #e4e6ef;
        border-radius: 0.475rem;
        background: #fff;
        box-shadow: none;
        display: flex;
        align-items: center;
        padding: 0.1rem 0.55rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .comment-user-selector .tagify:hover {
        border-color: #b5b5c3;
    }

    .comment-user-selector .tagify.tagify--focus {
        border-color: #009ef7;
        box-shadow: 0 0 0 0.2rem rgba(0, 158, 247, 0.15);
    }

    .comment-user-selector .tagify__input {
        margin: 0;
        padding: 0;
        min-width: 0;
        line-height: 1.5;
        font-size: 0.95rem;
    }

    .comment-user-selector .tagify__tag {
        margin: 0;
        max-width: 100%;
        border-radius: 0.375rem;
        background: #eef6ff;
        border: 1px solid #d6e9ff;
    }

    .comment-user-selector .tagify__tag > div {
        padding: 0.18rem 0.45rem;
    }

    .comment-user-selector .tagify__tag-text {
        font-size: 0.9rem;
        line-height: 1.2;
    }

    .comment-user-selector .tagify__tag__removeBtn {
        margin-inline-start: 0.2rem;
    }

    .comment-user-selector .tagify__dropdown__wrapper {
        border-radius: 0.475rem;
    }
</style>

<div class="row">
    <div class="col-12">

        {{-- Existing Comments --}}
        @if($comments->isEmpty())
            <p class="text-muted">@lang('wncms::word.no_comments')</p>
        @else
            <div class="comment-toolbar">
                <p class="text-muted mb-0">@lang('wncms::word.comment_count', ['count' => $post->getCommentCount()])</p>
                <form method="GET" action="{{ route('posts.edit', ['id' => $post->id]) }}" class="comment-toolbar-form">
                    <input type="hidden" name="tab" value="comments">
                    <label for="comments_order" class="small text-muted mb-0">@lang('wncms::word.order')</label>
                    <select id="comments_order" name="comments_order" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="newest" @selected(($commentOrder ?? 'newest') === 'newest')>@lang('wncms::word.comment_order_newest')</option>
                        <option value="oldest" @selected(($commentOrder ?? 'newest') === 'oldest')>@lang('wncms::word.comment_order_oldest')</option>
                    </select>
                </form>
            </div>
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
                <input type="hidden" name="active_tab" value="comments">
                <input type="hidden" name="commentable_type" value="{{ get_class($post) }}">
                <input type="hidden" name="commentable_id" value="{{ $post->id }}">

                <div class="d-flex">
                    <div class="me-3">
                        <div class="avatar-circle bg-secondary text-white fw-bold">
                            {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">@lang('wncms::word.author')</label>
                                @include('wncms::backend.posts.comment-user-selector', [
                                    'selectedUserId' => optional(auth()->user())->id,
                                    'selectedUser' => auth()->user(),
                                ])
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">@lang('wncms::word.status')</label>
                                <select name="status" class="form-select form-select-sm">
                                    @foreach($commentStatuses as $commentStatus)
                                        <option value="{{ $commentStatus }}">{{ __('wncms::word.' . $commentStatus) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <textarea name="content" rows="3" class="form-control mb-2" placeholder="@lang('wncms::word.write_a_comment')"></textarea>
                        <button type="submit" class="btn btn-dark btn-sm">@lang('wncms::word.add_comment')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
