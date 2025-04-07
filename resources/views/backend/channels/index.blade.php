@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('channels.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- exampleItem for example_item --}}
                {{-- @if(!empty($exampleItems))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="example_item_id" class="form-select form-select-sm">
                            <option value="">@lang('wncms::word.select')@lang('wncms::word.example_item')</option>
                            @foreach($exampleItems as $exampleItem)
                                <option value="{{ $exampleItem->id }}" @if($exampleItem->id == request()->example_item_id) selected @endif>{{ $exampleItem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif --}}

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('wncms::word.' . $show)</label>
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
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'channels',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $channels])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle text-nowrap mb-0">

                    {{-- thead --}}
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            {{-- Checkbox --}}
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms::word.slug')</th>
                            <th>@lang('wncms::word.clicks')</th>
                            <th>@lang('wncms::word.contact')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.created_at')</th>

                            @if(request()->show_detail)
                            <th>@lang('wncms::word.updated_at')</th>
                            @endif
                            
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($channels as $channel)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $channel->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('channels.edit' , $channel) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete' , ['model'=>$channel , 'route' => route('channels.destroy' , $channel), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $channel->id }}</td>
                                <td>{{ $channel->name }}</td>
                                <td>{{ $channel->slug }}</td>
                                <td>{{ $channel->clicks_count }}</td>
                                <td>{{ $channel->contact }}</td>
                                <td>{{ $channel->remark }}</td>
                                <td>{{ $channel->created_at }}</td>
                                
                                @if(request()->show_detail)
                                <td>{{ $channel->updated_at }}</td>
                                @endif
                                
                            <tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $channels])

    {{-- Pagination --}}
    {{-- <div class="mt-5">
        {{ $channels->withQueryString()->links() }}
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