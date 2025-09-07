<div class="comment mb-4">
    {{-- Avatar --}}
    <div class="me-3">
        <div class="avatar-circle bg-dark text-white fw-bold">
            {{ strtoupper(substr($comment->user?->username ?? 'G', 0, 1)) }}
        </div>
    </div>

    {{-- Body --}}
    <div class="comment-body flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <strong>{{ $comment->user?->username ?? 'Guest' }}</strong>
                <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }} #{{ $comment->id }}</small>
            </div>

            {{-- Delete button --}}
            <form method="POST" action="{{ route('comments.destroy', $comment->id) }}" onsubmit="return confirm('@lang('wncms::word.confirm_delete')');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger px-2 py-1">@lang('wncms::word.delete')</button>
            </form>
        </div>

        <div class="comment-content mb-2">
            {{ $comment->content }}
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
