@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')


    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('search_keywords.index') }}">
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
                'model_prefix' => 'search_keywords',
            ])
        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
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
                        @foreach($search_keywords as $search_keyword)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $search_keyword->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('search_keywords.edit' , $search_keyword) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$search_keyword , 'route' => route('search_keywords.destroy' , $search_keyword), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $search_keyword->id }}</td>
                            <td>{{ $search_keyword->name }}</td>

                            @if(request()->show_detail)
                            <td>{{ $search_keyword->updated_at }}</td>
                            @endif
                            
                            <td>{{ $search_keyword->created_at }}</td>
                            <td>{{ $search_keyword->remark }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection