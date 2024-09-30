@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('plugins.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- exampleItem for example_item --}}
                {{-- @if(!empty($exampleItems))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="example_item_id" class="form-select form-select-sm">
                            <option value="">@lang('word.select')@lang('word.example_item')</option>
                            @foreach($exampleItems as $exampleItem)
                                <option value="{{ $exampleItem->id }}" @if($exampleItem->id == request()->example_item_id) selected @endif>{{ $exampleItem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif --}}

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
                'model_prefix' => 'plugins',
            ])

            {{-- upload_plugin --}}
            <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_upload_plugin">{{ wncms_model_word('plugin', 'upload') }}</button>
            <div class="modal fade" tabindex="-1" id="modal_upload_plugin">
                <div class="modal-dialog">
                    <form action="{{ route('plugins.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('word.upload_plugin')</h3>
                            </div>
                
                            <div class="modal-body">
                                <div class="form-item">
                                    <input type="file" name="plugin_file">
                                </div>
                            </div>
                
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                <button type="submit" class="btn btn-primary fw-bold">@lang('word.submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $plugins])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">

                    {{-- thead --}}
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            {{-- Checkbox --}}
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>@lang('word.id')</th>
                            <th>@lang('word.name')</th>
                            <th>@lang('word.description')</th>
                            <th>@lang('word.url')</th>
                            <th>@lang('word.author')</th>
                            <th>@lang('word.version')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.path')</th>
                            <th>@lang('word.remark')</th>
                            <th>@lang('word.created_at')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($plugins as $plugin)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $plugin->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('plugins.edit' , $plugin) }}">@lang('word.edit')</a>
                                    @include('backend.parts.modal_delete' , ['model'=>$plugin , 'route' => route('plugins.destroy' , $plugin), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $plugin->id }}</td>
                                <td>{{ $plugin->name }}</td>
                                <td>{{ $plugin->description }}</td>
                                <td>{{ $plugin->url }}</td>
                                <td>{{ $plugin->author }}</td>
                                <td>{{ $plugin->version }}</td>
                                <td>{{ $plugin->status }}</td>
                                <td>{{ $plugin->path }}</td>
                                <td>{{ $plugin->remark }}</td>
                                <td>{{ $plugin->created_at }}</td>

                                @if(request()->show_detail)
                                <td>{{ $plugin->updated_at }}</td>
                                @endif
                                
                            <tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $plugins])

    {{-- Pagination --}}
    {{-- <div class="mt-5">
        {{ $plugins->withQueryString()->links() }}
    </div> --}}

@endsection

@push('foot_js')
    <script>
        //修改checkbox時直接提交
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