@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')



    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('users.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('word.' . $show)</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('backend.common.default_toolbar_buttons', [
                'model_prefix' => 'users',
            ])
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle text-nowrap mb-0" id="kt_ecommerce_report_sales_table">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th>@lang('word.action')</th>
                            <th>@lang('word.user_id')</th>
                            <th>@lang('word.website')</th>
                            <th>@lang('word.username')</th>
                            <th>@lang('word.email')</th>
                            <th>@lang('word.role')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.api_token')</th>
                            <th>@lang('word.first_name')</th>
                            <th>@lang('word.last_name')</th>
                            <th>@lang('word.email_verified_at')</th>
                            <th>@lang('word.last_login_at')</th>
                            @endif
                    
                            @if(request()->show_detail)
                            <th>@lang('word.locale')</th>
                            <th>@lang('word.timezone')</th>
                            <th>@lang('word.social_login_type')</th>
                            <th>@lang('word.social_login_id')</th>
                            @endif
                            
                            <th>@lang('word.credit')</th>
                            <th>@lang('word.created_at')</th>
                            <th>@lang('word.updated_at')</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @foreach($users as $user)
                        <tr>
                            <td>
                                {{-- @include('backend.users.parts.modal_recharge_by_admin', ['user' => $user]) --}}
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('users.edit' , $user) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$user , 'route' => route('users.destroy' , $user)])

                                {{-- @if($user->trashed())
                                @include('backend.parts.modal_delete' , ['model'=>$user , 'route' => route('users.destroy.force' , $user), 'btn_text' => __('word.force_delete'), 'target' => 'form_delete'])
                                @endif --}}
                            </td>
                            <td>{{ $user->id }}</td>
                            <td>
                                @foreach($user->websites as $userWebsite)
                                <a href="{{ $userWebsite->url }}" target="_blank" title="{{ $userWebsite->domain }}">#{{ $userWebsite->id }}</a>
                                @endforeach
                            </td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    @if($role->name == 'admin')
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                    @else
                                    <span class="badge bg-success">{{ $role->name }}</span>
                                    @endif
                                @endforeach
                            </td>

                            @if(request()->show_detail)
                            <td><input type="text" class="form-control form-control-sm w-250px" value="{{ $user->api_token }}" disabled></td>
                            <td>{{ $user->first_name }}</td>
                            <td>{{ $user->last_name }}</td>
                            <td>{{ $user->email_verified_at }}</td>
                            <td>{{ $user->last_login_at }}</td>
                            @endif

                            @if(request()->show_detail)
                            <td>{{ $user->locale }}</td>
                            <td>{{ $user->timezone }}</td>

                            <td>{{ $user->social_login_type }}</td>
                            <td>{{ $user->social_login_id }}</td>
                            @endif
                            
                            <td>{{ $user->credit }}</td>
                            <td>{{ $user->created_at }}</td>
                            <td>{{ $user->updated_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $users->links() }}

@endsection

@push('foot_js')
    <script>
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>
@endpush