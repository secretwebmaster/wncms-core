@extends("$themeId::layouts.app")

@section('content')

<h2>
    @lang("$themeId::word.post_management")
</h2>

<form method="POST" action="{{ route('frontend.posts.update', $post['id']) }}">
    @csrf

    <div>
        <label>@lang("$themeId::word.title")</label>
        <input type="text" name="title" value="{{ old('title', $post['title']) }}" required>
    </div>

    <div>
        <label>@lang("$themeId::word.slug")</label>
        <input type="text" name="slug" value="{{ old('slug', $post['slug']) }}">
    </div>

    <div>
        <label>@lang("$themeId::word.excerpt")</label>
        <textarea name="excerpt">{{ old('excerpt', $post['excerpt']) }}</textarea>
    </div>

    <div>
        <label>@lang("$themeId::word.content")</label>
        <textarea name="content" rows="6">{{ old('content', $post['content']) }}</textarea>
    </div>

    @if (!empty($categories))
        <div>
            <label>@lang("$themeId::word.categories")</label>
            <select name="categories" multiple>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}"
                        @if ($post['category'] && $post['category']->contains('id', $cat->id)) selected @endif>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label>@lang("$themeId::word.tags")</label>
        <input type="text" name="tags" value="{{ old('tags', implode(',', $post['tags']->toArray())) }}">
        <small>@lang("$themeId::word.comma_separated")</small>
    </div>

    <button type="submit">
        @lang("$themeId::word.submit")
    </button>

</form>

@endsection
