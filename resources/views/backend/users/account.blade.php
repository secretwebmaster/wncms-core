@extends('wncms::layouts.backend')

@section('content')

    <div class="row">

        {{-- Basic info --}}
        <div class="col-12 col-md-8">

            {{-- Nav --}}
            @include('wncms::backend.users.parts.nav')

            @include('wncms::backend.parts.message')

            {{-- Content --}}
            @yield('account_content')
            
        </div>

        {{-- info card --}}
        <div class="col-12 col-md-4">
            <h2 class="mb-5">@lang('wncms::word.preview')</h2>
            @include('wncms::backend.users.parts.info')
        </div>

    </div>
@endsection



