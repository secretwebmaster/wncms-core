@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('contact_forms.index') }}">
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
                'model_prefix' => 'contact_forms',
            ])

            {{-- Create contact form options --}}
            @if(wncms_route_exists('contact_form_options.create'))
            <a href="{{ route('contact_form_options.create') }}" class="btn btn-sm btn-info fw-bold mb-1">{{ wncms_model_word('contact_form_options','create') }}</a>
            @endif

            {{-- list contact form options --}}
            @if(wncms_route_exists('contact_form_options.index'))
            <a href="{{ route('contact_form_options.index') }}" class="btn btn-sm btn-info fw-bold mb-1">{{ wncms_model_word('contact_form_options','index') }}</a>
            @endif

            {{-- list contact form submission --}}
            @if(wncms_route_exists('contact_form_submissions.index'))
            <a href="{{ route('contact_form_submissions.index') }}" class="btn btn-sm btn-dark fw-bold mb-1">{{ wncms_model_word('contact_form_submissions','index') }}</a>
            @endif

        </div>
    </div>

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
                            <th>@lang('word.title')</th>
                            <th>@lang('word.description')</th>
                            <th>@lang('word.call_function')</th>
                            <th>@lang('word.remark')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                            <th>@lang('word.created_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($contact_forms as $contact_form)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $contact_form->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('contact_forms.edit' , $contact_form) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$contact_form , 'route' => route('contact_forms.destroy' , $contact_form), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $contact_form->id }}</td>
                            <td>{{ $contact_form->name }}</td>
                            <td>{{ $contact_form->title }}</td>
                            <td>{{ $contact_form->description }}</td>
                            <td>
                                <div class="input-group w-300px">
                                    <input type="text" class="form-control form-control-sm" value="$wncms->contact_form()->render(contactFormId:{{ $contact_form->id }})">
                                    <button class="btn btn-sm btn-primary fw-bold" btn-copy-to-clipboard data-original-text="@lang('word.copy')" data-copied-text="@lang('word.copied')" data-value="$wncms->contact_form()->render(contactFormId:{{ $contact_form->id }})">@lang('word.copy')</button>
                                </div>
                            </td>
                            <td>{{ $contact_form->remark }}</td>
                            

                            @if(request()->show_detail)
                            <td>{{ $contact_form->updated_at }}</td>
                            @endif
                            
                            <td>{{ $contact_form->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    {{-- {{ $contact_form->withQueryString()->links() }} --}}

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