@if ($errors->any())
    <div class="wncms-system-message fw-bold alert alert-danger">
        <ul class="mb-0 p-0 list-unstyled">
            @foreach ($errors->all() as $error)
            <li>{!! $error !!}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session()->has('message'))
    <div class="wncms-system-message fw-bold alert @if(session()->has('status') && session('status') == 'fail') alert-danger @else alert-success @endif">{!! session('message') !!}</div>
@endif

@if (session()->has('info'))
    <div class="wncms-system-message fw-bold alert @if(session()->has('status') && session('status') == 'fail') alert-danger @else alert-info @endif">{!! session('info') !!}</div>
@endif

@if (session()->has('error_message'))
    <div class="wncms-system-message fw-bold alert alert-danger">{!! session('error_message') !!}</div>
@endif

@if (session()->has('error'))
    <div class="wncms-system-message fw-bold alert alert-danger">{!! session('error') !!}</div>
@endif

{{-- ajax --}}
<div class="wncms-system-message fw-bold alert alert-danger ajax_messag fail" style="display:none;">
    <ul class="mb-0 p-0 list-unstyled">
        <li class="message_content">{!! session('error_message') !!}</li>
    </ul>
</div>

<div class="wncms-system-message fw-bold alert alert-success ajax_message success" style="display:none;">
    <ul class="mb-0 p-0 list-unstyled">
        <li class="message_content">{!! session('error_message') !!}</li>
    </ul>
</div>

