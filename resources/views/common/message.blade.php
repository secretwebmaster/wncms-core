@if ($errors->any())
    <div class="fw-bold alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{!! $error !!}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session()->has('message'))
    <div class="fw-bold alert @if(session()->has('status') && session('status') == 'fail') alert-danger @else alert-success @endif">{!! session('message') !!}</div>
@endif

@if (session()->has('info'))
    <div class="fw-bold alert @if(session()->has('status') && session('status') == 'fail') alert-danger @else alert-info @endif">{!! session('info') !!}</div>
@endif

@if (session()->has('error_message'))
    <div class="fw-bold alert alert-danger">{!! session('error_message') !!}</div>
@endif

{{-- ajax --}}
<div class="fw-bold alert alert-danger ajax_messag fail" style="display:none;">
    <ul class="mb-0">
        <li class="message_content">{!! session('error_message') !!}</li>
    </ul>
</div>

<div class="fw-bold alert alert-success ajax_message success" style="display:none;">
    <ul class="mb-0">
        <li class="message_content">{!! session('error_message') !!}</li>
    </ul>
</div>

