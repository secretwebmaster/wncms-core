@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('permissions.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach([] as $show)
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
                'model_prefix' => 'permissions',
            ])
            {{-- assign role --}}
            <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modal_bulk_assign_roles_form">@lang('word.bulk_assign_roles')</button>
            <div class="modal fade" tabindex="-1" id="modal_bulk_assign_roles_form">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.bulk_assign_roles')</h3>
                        </div>

                        <form id="bulk_assign_roles_form" action="{{ route('permissions.bulk_assign_roles') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                {{-- roles --}}
                                <label class="form-label fw-bold fs-6">@lang('word.roles')</label>
                                <div class="row align-items-center mt-3">
                                    @foreach($roles as $index => $role)
                                        <div class="col-6 col-md-4 mb-1">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="role_ids[{{ $role->id }}]" type="checkbox"/>
                                                <span class="fw-bold ps-2 fs-6">@lang('word.' . $role->name)</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                <button type="submit" class="btn btn-primary fw-bold btn_bulk_assign_roles">@lang('word.submit')</button>
                            </div>                        
                        </form>
                        @push('foot_js')
                            <script>
                                $('.btn_bulk_assign_roles').on('click', function(e){
                                    e.preventDefault();
                                    var permission_ids = WNCMS.CheckBox.Ids()
                                    // 创建隐藏input元素

                                    var hiddenInput = $('<input>').attr({
                                        type: 'hidden',
                                        name: 'permission_ids',
                                        value: permission_ids
                                    });

                                    // 将隐藏input元素添加到表单中
                                    $('#bulk_assign_roles_form').append(hiddenInput);

                                    // 提交表单
                                    $('#bulk_assign_roles_form').submit();
                                })
                            </script>
                            
                        @endpush

                    </div>
                </div>
            </div>

            {{-- remvoe role --}}
            <button type="button" class="btn btn-sm btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#modal_bulk_remove_roles_form">@lang('word.bulk_remove_roles')</button>
            <div class="modal fade" tabindex="-1" id="modal_bulk_remove_roles_form">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.bulk_remove_roles')</h3>
                        </div>

                        <form id="bulk_remove_roles_form" action="{{ route('permissions.bulk_remove_roles') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                {{-- roles --}}
                                <label class="form-label fw-bold fs-6">@lang('word.roles')</label>
                                <div class="row align-items-center mt-3">
                                    @foreach($roles as $index => $role)
                                        <div class="col-6 col-md-4 mb-1">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="role_ids[{{ $role->id }}]" type="checkbox"/>
                                                <span class="fw-bold ps-2 fs-6">@lang('word.' . $role->name)</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                <button type="submit" class="btn btn-danger fw-bold btn_bulk_remove_roles">@lang('word.submit')</button>
                            </div>                        
                        </form>
                        @push('foot_js')
                            <script>
                                $('.btn_bulk_remove_roles').on('click', function(e){
                                    e.preventDefault();
                                    var permission_ids = WNCMS.CheckBox.Ids()
                                    // 创建隐藏input元素

                                    var hiddenInput = $('<input>').attr({
                                        type: 'hidden',
                                        name: 'permission_ids',
                                        value: permission_ids
                                    });

                                    // 将隐藏input元素添加到表单中
                                    $('#bulk_remove_roles_form').append(hiddenInput);

                                    // 提交表单
                                    $('#bulk_remove_roles_form').submit();
                                })
                            </script>
                            
                        @endpush

                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-xs table-bordered table-hover align-middle text-nowrap mb-0 border border-2 border-dark">
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
                            <th>@lang('word.roles')</th>
                            <th>@lang('word.created_at')</th>
                            <th>@lang('word.updated_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($permissions as $permission)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $permission->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('permissions.edit' , $permission) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$permission , 'route' => route('permissions.destroy' , $permission), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $permission->id }}</td>
                            <td>{{ $permission->name }}</td>
                            <td>
                                @foreach($permission->roles as $role)
                                    @if($role->name == 'superadmin')
                                        <span class="badge badge-info fw-bold">@lang('word.' . $role->name)</span>
                                    @elseif($role->name == 'admin')
                                        <span class="badge badge-primary fw-bold">@lang('word.' . $role->name)</span>
                                    @elseif($role->name == 'manager')
                                        <span class="badge badge-success fw-bold">@lang('word.' . $role->name)</span>
                                    @elseif($role->name == 'member')
                                        <span class="badge badge-dark fw-bold">@lang('word.' . $role->name)</span>
                                    @elseif($role->name == 'suspended')
                                        <span class="badge badge-danger fw-bold">@lang('word.' . $role->name)</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>{{ $permission->created_at }}</td>
                            <td>{{ $permission->updated_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5">
        {{ $permissions->withQueryString()->links() }}
    </div>
    
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