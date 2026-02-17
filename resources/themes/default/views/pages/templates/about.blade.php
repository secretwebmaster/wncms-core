@extends("$themeId::layouts.app")

@section('content')
<div class="container py-5">
    <h1 class="mb-4">{{ $page->title }}</h1>

    @php
        $heroImage = $page->option('content.hero_image', '');
        $summary = $page->option('content.summary', '');
    @endphp

    @if (!empty($heroImage))
        <div class="mb-4">
            <img src="{{ asset($heroImage) }}" alt="hero" class="img-fluid rounded">
        </div>
    @endif

    @if (!empty($summary))
        <div class="card">
            <div class="card-body">{{ $summary }}</div>
        </div>
    @endif

    @if (empty($heroImage) && empty($summary))
        <div class="alert alert-info">No template options saved yet.</div>
    @endif
</div>
@endsection
