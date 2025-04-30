@extends('frontend.theme.default.layouts.app')

@section('content')

    {{-- Pages --}}
    @include('frontend.theme.default.parts.page-list')

    {{-- Posts --}}
    @include('frontend.theme.default.parts.post-list')

    {{-- Tags --}}
    @include('frontend.theme.default.parts.tag-list')

    {{-- Webstie options --}}
    @include('frontend.theme.default.parts.website-options')
    
    {{-- Thenme options --}}
    @include('frontend.theme.default.parts.theme-options')

    {{-- Links --}}
    @include('frontend.theme.default.parts.link-list')
@endsection

@push('foot_js')
    @include('wncms::frontend.common.clicks.record')
@endpush

{{-- @push('foot_js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const previousPage = sessionStorage.getItem('previousPage') || document.referrer;
        console.log('previousPage: ' + previousPage);

        document.querySelectorAll('[wncms-click-record]').forEach(function (el) {
            el.addEventListener('click', function () {
                const clickableId = el.dataset.clickableId;
                const clickableType = el.dataset.clickableType;
                const name = el.dataset.name || null;
                const value = el.dataset.value || null;

                const urlParams = new URLSearchParams(window.location.search);
                const channel = urlParams.get('channel') || '';
                const referer = document.referrer || '';

                const parameters = {};
                urlParams.forEach((value, key) => {
                    parameters[key] = value;
                });

                console.log(parameters);

                fetch("{{ route('frontend.clicks.record') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        clickable_id: clickableId,
                        clickable_type: clickableType,
                        channel: channel,
                        name: name,
                        value: value,
                        referer: referer,
                        parameters: parameters
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Click recorded:", data);
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            });
        });
    });
</script>
@endpush --}}
