@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        {{-- Details --}}
        @include('backend.users.parts.info')

        {{-- Nav --}}
        @include('backend.users.parts.nav')
    </div>
</div>

{{-- invite_friedns_to_get_reward --}}
<div class="card mb-5 mb-xl-10">

    {{-- Card header --}}
    <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_signin_method">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0">@lang('word.invite_friedns_to_get_reward')</h3>
        </div>
    </div>


    {{-- Content --}}
    <div id="kt_account_settings_signin_method" class="collapse show">
        {{-- Card body --}}
        <div class="card-body border-top p-3 p-md-9">

            <div class="border border-2 border-dark rounded">

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap mb-0">
                        <thead class="table-dark">
                            <tr class="text-start fw-bold gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom me-3">
                                        <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                    </div>
                                </th>
                                <th>@lang('word.action')</th>
                                <th>@lang('word.id')</th>
                                <th>@lang('word.name')</th>

                                @if(request()->show_detail)
                                <th>@lang('word.updated_at')</th>
                                @endif

                                <th>@lang('word.created_at')</th>
                            </tr>
                        </thead>
                        <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                            @foreach([] as $user)
                            <tr>
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $user->id }}" />
                                    </div>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('users.edit' , $user) }}">@lang('word.edit')</a>
                                    @include('backend.parts.modal_delete' , ['model'=>$user , 'route' => route('users.destroy' , $user), 'btn_class' => 'px-2 py-1'])
                                </td>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>

                                @if(request()->show_detail)
                                <td>{{ $user->updated_at }}</td>
                                @endif

                                <td>{{ $user->created_at }}</td>
                            <tr>
                                @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    </div>
    
</div>





@endsection