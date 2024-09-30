@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('menus.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>
        </form>
    </div>
    
    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('backend.common.default_toolbar_buttons', [
                'model_prefix' => 'menus',
            ])

            {{-- clone_menu --}}
            <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_clone_menu">@lang('word.clone_menu')</button>
            <div class="modal fade" tabindex="-1" id="modal_clone_menu">
                <div class="modal-dialog">
                    <form action="{{ route('menus.clone') }}" method="POST" id="form_clone_menu">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('word.clone_menu')</h3>
                            </div>

                            
                
                            <div class="modal-body">
                                <div class="form-item mb-5">
                                    <label class="form-label">@lang('word.select_options')</label>
                                    <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                                        <input type="hidden" name="create_page_if_not_exists" value="0">
                                        <input class="form-check-input w-35px h-20px border border-1 border-secondary" type="checkbox" name="create_page_if_not_exists" value="1">
                                        <label class="form-check-label" for="create_page_if_not_exists">@lang('word.create_page_if_not_exists')</label>
                                    </div>
                                    
                                </div>
 
                                <div class="form-item mb-5">
                                    <label class="form-label">@lang('word.select_websites')</label>
                                    <div class="row">
                                        @foreach($websites as $_website)
                                        <div class="col-12 col-md-6 mb-1">
                                            <label class="form-check form-check-inline form-check-solid me-5">
                                                <input class="form-check-input" name="website_ids[]" type="checkbox" value="{{ $_website->id }}">
                                                <span class="ps-1 fs-6">{{ $_website->domain }}</span>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                <button type="button" class="btn btn-primary fw-bold"
                                    wncms-btn-ajax
                                    wncms-get-model-ids
                                    wncms-btn-swal
                                    data-form="form_clone_menu"
                                    data-original-text="@lang('word.submit')"
                                    data-loading-text="@lang('word.loading').."
                                    data-success-text="@lang('word.submit')"
                                    data-fail-text="@lang('word.retry')"
                                    data-route="{{ route('menus.clone') }}"
                                    data-method="POST"
                                    data-param-column="column"
                                >@lang('word.submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1"/>
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>ID</th>
                            <th>@lang('word.website')</th>
                            <th>@lang('word.menu_name')</th>
                            <th>@lang('word.updated_at')</th>
                            <th>@lang('word.created_at')</th>

                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($menus as $menu)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $menu->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('menus.edit' , $menu) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$menu , 'route' => route('menus.destroy' , $menu)])
                            </td>
                            <td>{{ $menu->id }}</td>
                            <td>{{ $menu->website?->domain }}</td>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $menu->updated_at }}</td>
                            <td>{{ $menu->created_at }}</td>

                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $menus->withQueryString()->links() }}
    </div>

@endsection