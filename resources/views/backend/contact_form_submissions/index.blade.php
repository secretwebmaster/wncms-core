@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('contact_form_submissions.index') }}">
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
                'model_prefix' => 'contact_form_submissions',
            ])

            <button class="btn btn-sm btn-success fw-bold mb-1"
                wncms-btn-ajax
                wncms-btn-swal
                wncms-get-model-ids
                data-swal="true"
                data-route="{{ route('models.update') }}"
                data-method="POST"
                data-param-model="ContactFormSubmission"
                data-param-column="status"
                data-param-value="read"
            >@lang('word.bulk_set_read')</button>

            <button class="btn btn-sm btn-info fw-bold mb-1"
                wncms-btn-ajax
                wncms-btn-swal
                wncms-get-model-ids
                data-swal="true"
                data-route="{{ route('models.update') }}"
                data-method="POST"
                data-param-model="ContactFormSubmission"
                data-param-column="status"
                data-param-value="replied"
            >@lang('word.bulk_set_replied')</button>

            <button class="btn btn-sm btn-danger fw-bold mb-1"
                wncms-btn-ajax
                wncms-btn-swal
                wncms-get-model-ids
                data-swal="true"
                data-route="{{ route('models.update') }}"
                data-method="POST"
                data-param-model="ContactFormSubmission"
                data-param-column="status"
                data-param-value="unread"
            >@lang('word.bulk_set_unread')</button>

            @include('backend.common.export_index_data', [
                'modelName' => 'contact_form_submissions',
            ])

        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>@lang('word.id')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.contact_form_id')</th>
                            <th>@lang('word.content')</th>
                            
                            @foreach($allKeys as $key)
                            <th>{{ $key }}</th>
                            @endforeach

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                            <th>@lang('word.created_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600 align-top">
                        @foreach($contact_form_submissions as $index => $contact_form_submission)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $contact_form_submission->id }}"/>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success fw-bold px-2 py-1" 
                                    wncms-btn-ajax
                                    wncms-btn-swal
                                    data-route="{{ route('models.update') }}"
                                    data-param-model-id="{{ $contact_form_submission->id }}" 
                                    data-param-model="ContactFormSubmission" 
                                    data-param-column="status" 
                                    data-param-value="read"
                                >@lang('word.mark_read')</button>

                                <button class="btn btn-sm btn-info fw-bold px-2 py-1" 
                                    wncms-btn-ajax
                                    wncms-btn-swal
                                    data-route="{{ route('models.update') }}"
                                    data-param-model-id="{{ $contact_form_submission->id }}" 
                                    data-param-model="ContactFormSubmission" 
                                    data-param-column="status" 
                                    data-param-value="replied"
                                >@lang('word.mark_replied')</button>

                                <button class="btn btn-sm btn-danger fw-bold px-2 py-1" 
                                    wncms-btn-ajax
                                    wncms-btn-swal
                                    data-route="{{ route('models.update') }}"
                                    data-param-model-id="{{ $contact_form_submission->id }}" 
                                    data-param-model="ContactFormSubmission" 
                                    data-param-column="status" 
                                    data-param-value="unread"
                                >@lang('word.mark_unread')</button>

                                @include('backend.parts.modal_delete' , ['model'=>$contact_form_submission , 'route' => route('contact_form_submissions.destroy' , $contact_form_submission), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $contact_form_submission->id }}</td>
                            <td>@include('common.table_status', ['model' => $contact_form_submission])</td>
                            <td>
                                @if($contact_form_submission->contact_form)
                                {{ $contact_form_submission->contact_form?->name }} ({{ $contact_form_submission->contact_form?->id }})
                                @endif
                            </td>
                            <td>
                                {{-- view_contact_form_content_detail --}}
                                <button type="button" class="btn btn-sm px-2 py-1 btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modal_view_contact_form_content_detail_{{ $contact_form_submission->id }}">@lang('word.view_contact_form_content_detail')</button>
                                <div class="modal fade" tabindex="-1" id="modal_view_contact_form_content_detail_{{ $contact_form_submission->id }}">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">@lang('word.view_contact_form_content_detail')</h3>
                                            </div>
                                
                                            <div class="modal-body">
                                                <table class="table">
                                                    <thead>
                                                        <td>@lang('word.key')</td>
                                                        <td>@lang('word.value')</td>
                                                    </thead>
                                                    <tbody>

                                                        @foreach(collect($contact_form_submission->content ?? [])->sort() as $field_name => $field_value)
                                                            <tr>
                                                                <td>{{ $contact_form_submission->contact_form?->getOptionDisplayName($field_name) }}</td>
                                                                <td class="mw-100px text-truncate">
                                                                    @if(strpos($field_value, 'http') !== false)
                                                                    <a href="{{ wncms_add_https($field_value) }}" target="_blank">{{ $field_value }}</a>
                                                                    @else
                                                                    <span>{{ $field_value }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                            </div>
                                
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                                <button type="button" class="btn btn-primary fw-bold">@lang('word.submit')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            @foreach($allKeys as $key)
                                <td class="mw-100px text-truncate" title="{{ $contact_form_submission->content[$key] ?? '' }}">
                                    @if(strpos($field_value, 'http') !== false)
                                        <a href="{{ wncms_add_https($field_value) }}" target="_blank">{{ $field_value }}</a>
                                    @else
                                        <span>{{ $contact_form_submission->content[$key] ?? '' }}</span>
                                    @endif
                                </td>
                            @endforeach

                            @if(request()->show_detail)
                            <td>{{ $contact_form_submission->updated_at }}</td>
                            @endif
                            
                            <td>{{ $contact_form_submission->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $contact_form_submissions->withQueryString()->links() }}
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