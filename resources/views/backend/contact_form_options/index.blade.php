@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('contact_form_options.index') }}">
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
                'model_prefix' => 'contact_form_options',
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
                            <th>@lang('word.type')</th>
                            <th>@lang('word.display_name')</th>
                            <th>@lang('word.placeholder')</th>
                            <th>@lang('word.default_value')</th>
                            <th>@lang('word.options')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                            <th>@lang('word.created_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($contact_form_options as $contact_form_option)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $contact_form_option->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('contact_form_options.edit' , $contact_form_option) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$contact_form_option , 'route' => route('contact_form_options.destroy' , $contact_form_option), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $contact_form_option->id }}</td>
                            <td>{{ $contact_form_option->name }}</td>
                            <td>{{ $contact_form_option->type }}</td>
                            <td>{{ $contact_form_option->display_name }}</td>
                            <td>{{ $contact_form_option->placeholder }}</td>
                            <td>{{ $contact_form_option->default_value }}</td>
                            <td>{{ $contact_form_option->options }}</td>

                            @if(request()->show_detail)
                            <td>{{ $contact_form_option->updated_at }}</td>
                            @endif
                            
                            <td>{{ $contact_form_option->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- {{ $contact_form_option->withQueryString()->links() }} --}}

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