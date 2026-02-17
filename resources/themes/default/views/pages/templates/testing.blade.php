@extends("$themeId::layouts.app")

@section('content')
<div class="container py-5">
    <h1 class="mb-4">{{ $page->title }}</h1>

    @php
        $switchTarget = $page->option('switch_test.switch_target', '');
        $decoded = is_string($switchTarget) ? json_decode($switchTarget, true) : null;
        $isJson = is_array($decoded) && json_last_error() === JSON_ERROR_NONE;
    @endphp

    <div class="card mb-4">
        <div class="card-header">Template Option Debug</div>
        <div class="card-body">
            <div class="mb-2"><strong>Option Key:</strong> <code>switch_test.switch_target</code></div>
            <div class="mb-2"><strong>Value Type:</strong> <code>{{ gettype($switchTarget) }}</code></div>

            <div class="mb-2"><strong>Raw Value:</strong></div>
            <pre class="bg-light p-3 border rounded">{{ is_array($switchTarget) ? json_encode($switchTarget, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $switchTarget }}</pre>

            <div class="mb-2"><strong>Decoded As JSON Array:</strong> <code>{{ $isJson ? 'yes' : 'no' }}</code></div>
        </div>
    </div>

    <div class="text-muted small">
        Test flow: save this field as <code>gallery</code>, then change config type to <code>text</code> and verify edit/save behavior.
    </div>
</div>
@endsection
