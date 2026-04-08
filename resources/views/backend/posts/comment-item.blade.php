<div class="comment mb-4 js-comment-item" data-comment-id="{{ $comment->id }}">
    {{-- Avatar --}}
    <div class="me-3">
        <div class="avatar-circle bg-dark text-white fw-bold">
            {{ strtoupper(substr($comment->user?->username ?? 'G', 0, 1)) }}
        </div>
    </div>

    {{-- Body --}}
    <div class="comment-body flex-grow-1">
        <div class="js-comment-view-panel">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                    <strong>{{ $comment->user?->username ?? __('wncms::word.guest') }}</strong>
                    <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }} #{{ $comment->id }} · {{ __('wncms::word.' . $comment->status) }}</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light px-2 py-1 js-comment-toggle" data-comment-toggle="reply">@lang('wncms::word.reply')</button>
                    <button type="button" class="btn btn-sm btn-secondary px-2 py-1 js-comment-toggle" data-comment-toggle="edit">@lang('wncms::word.edit')</button>

                    <form method="POST" action="{{ route('comments.destroy', $comment->id) }}" onsubmit="return confirm('@lang('wncms::word.confirm_delete')');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger px-2 py-1">@lang('wncms::word.delete')</button>
                    </form>
                </div>
            </div>

            <div class="comment-content mb-2 js-comment-display">
                {{ $comment->content }}
            </div>
        </div>

        <div class="js-comment-edit-panel d-none">
            <form method="POST" action="{{ route('comments.update', $comment->id) }}" class="comment-inline-editor">
                @csrf
                @method('PATCH')
                <input type="hidden" name="active_tab" value="comments">
                <input type="hidden" name="commentable_type" value="{{ $comment->commentable_type }}">
                <input type="hidden" name="commentable_id" value="{{ $comment->commentable_id }}">
                <input type="hidden" name="parent_id" value="{{ $comment->parent_id }}">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">@lang('wncms::word.author')</label>
                        @include('wncms::backend.posts.comment-user-selector', [
                            'selectedUserId' => $comment->user_id,
                            'selectedUser' => $comment->user,
                        ])
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">@lang('wncms::word.created_at')</label>
                        <input type="datetime-local" name="created_at" value="{{ optional($comment->created_at)->format('Y-m-d\\TH:i') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">@lang('wncms::word.status')</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach($commentStatuses as $commentStatus)
                                <option value="{{ $commentStatus }}" @selected($comment->status === $commentStatus)>{{ __('wncms::word.' . $commentStatus) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <textarea name="content" rows="4" class="form-control">{{ $comment->content }}</textarea>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-light js-comment-cancel" data-comment-cancel="edit">@lang('wncms::word.cancel')</button>
                    <button type="submit" class="btn btn-sm btn-dark">@lang('wncms::word.update')</button>
                </div>
            </form>
        </div>

        <div class="collapse js-comment-reply-panel">
            <div class="comment-reply-box mt-3">
                <form method="POST" action="{{ route('comments.store') }}">
                    @csrf
                    <input type="hidden" name="active_tab" value="comments">
                    <input type="hidden" name="commentable_type" value="{{ get_class($post) }}">
                    <input type="hidden" name="commentable_id" value="{{ $post->id }}">
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">@lang('wncms::word.author')</label>
                            @include('wncms::backend.posts.comment-user-selector', [
                                'selectedUserId' => optional(auth()->user())->id,
                                'selectedUser' => auth()->user(),
                            ])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">@lang('wncms::word.status')</label>
                            <select name="status" class="form-select form-select-sm">
                                @foreach($commentStatuses as $commentStatus)
                                    <option value="{{ $commentStatus }}">{{ __('wncms::word.' . $commentStatus) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <textarea name="content" rows="3" class="form-control" placeholder="@lang('wncms::word.write_a_comment')"></textarea>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-light js-comment-cancel" data-comment-cancel="reply">@lang('wncms::word.cancel')</button>
                        <button type="submit" class="btn btn-sm btn-dark">@lang('wncms::word.reply')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Replies shown below --}}
@if($comment->children->isNotEmpty())
    <div class="children">
        @foreach($comment->children as $child)
            @include('wncms::backend.posts.comment-item', ['comment' => $child])
        @endforeach
    </div>
@endif
