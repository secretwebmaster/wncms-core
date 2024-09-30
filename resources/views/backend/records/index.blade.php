@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('records.index') }}">
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
                'model_prefix' => 'records',
            ])
        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-xs table-hover align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>#</th>
                            <th>@lang('word.type')</th>
                            <th>@lang('word.sub_type')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.message')</th>
                            <th>@lang('word.detail')</th>
                            <th>@lang('word.created_at')</th>
                            <th>@lang('word.action')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($records as $record)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $record->id }}"/>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-2 py-1" data-bs-toggle="modal" data-bs-target="#modal_show_record_detail_{{ $record->id }}">@lang('word.show')</button>
                                <div class="modal fade" tabindex="-1" id="modal_show_record_detail_{{ $record->id }}">
                                    <div class="modal-dialog  modal-fullscreen">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">@lang('word.record') #{{ $record->id }}</h3>
                                            </div>
                                
                                            <div class="modal-body">
                                                <div class="form-item">
                                                    <label class="form-label">created_at</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->created_at }}" disabled>
                                                </div>
                                                <div class="form-item">
                                                    <label class="form-label">id</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->id }}" disabled>
                                                </div>
                                                <div class="form-item">
                                                    <label class="form-label">type</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->type }}" disabled>
                                                </div>
                                                <div class="form-item">
                                                    <label class="form-label">sub_type</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->sub_type }}" disabled>
                                                </div>
                                                <div class="form-item">
                                                    <label class="form-label">status</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->status }}" disabled>
                                                </div>
                                                <div class="form-item">
                                                    <label class="form-label">message</label>
                                                    <input type="text" class="form-control form-control-sm mb-3" value="{{ $record->message }}" disabled>
                                                </div>
                                                
                                                <div class="form-item">
                                                    <label class="form-label">detail</label><br>
                                                    <textarea class="form-control record_detail" rows=30>{!! $record->detail !!}</textarea>
                                                </div>

                                            </div>
                                
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                                                <button type="button" class="btn btn-primary">@lang('word.submit')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $record->id }}</td>
                            <td>{{ $record->type }}</td>
                            <td>{{ $record->sub_type }}</td>
                            <td>{{ $record->status }}</td>
                            <td>{{ $record->message }}</td>
                            <td>{{ $record->detail }}</td>
                            <td>{{ $record->created_at }}</td>
                            <td>
                                <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('records.edit' , $record) }}">@lang('word.edit')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$record , 'route' => route('records.destroy' , $record)])
                            </td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('foot_js')
    <script>
        window.addEventListener('DOMContentLoaded', function()
        {   
            var originals = $('.record_detail').each(function(){
                if($(this).text()){
                    try {
                        var text = JSON.parse($(this).text());
                        var pretty = JSON.stringify(text, undefined, 4);
                        $(this).text(pretty)
                    } catch (e) {
                        return false;
                    }
                }
            })
        })
    </script>
@endpush