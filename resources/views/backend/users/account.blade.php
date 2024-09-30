@extends('wncms::layouts.backend')

@section('content')

    <div class="row">

        {{-- info card --}}
        <div class="col-12 col-md-4">
            @include('wncms::backend.users.parts.info')
        </div>

        {{-- Basic info --}}
        <div class="col-12 col-md-8">

            {{-- Nav --}}
            @include('wncms::backend.users.parts.nav')

            @include('wncms::backend.parts.message')

            {{-- Content --}}
            @yield('account_content')
            
        </div>
    </div>
@endsection



