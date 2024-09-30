@extends('layouts.backend')

@section('content')

    <div class="row">

        {{-- info card --}}
        <div class="col-12 col-md-4">
            @include('backend.users.parts.info')
        </div>

        {{-- Basic info --}}
        <div class="col-12 col-md-8">

            {{-- Nav --}}
            @include('backend.users.parts.nav')

            @include('backend.parts.message')

            {{-- Content --}}
            @yield('account_content')
            
        </div>
    </div>
@endsection



