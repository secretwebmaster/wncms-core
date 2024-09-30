@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('faqs.index') }}">
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
                'model_prefix' => 'faqs',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $faqs])

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
                            <th>@lang('word.website')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.slug')</th>
                            <th>@lang('word.question')</th>
                            <th>@lang('word.answer')</th>
                            <th>@lang('word.faq_tag')</th>
                            <th>@lang('word.label')</th>
                            <th>@lang('word.remark')</th>
                            <th>@lang('word.order')</th>
                            <th>@lang('word.is_pinned')</th>
                            <th>@lang('word.created_at')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($faqs as $faq)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $faq->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('faqs.edit' , $faq) }}">@lang('word.edit')</a>
                                    <a class="btn btn-sm btn-info fw-bold px-2 py-1" href="{{ route('faqs.clone' , $faq) }}">@lang('word.clone')</a>
                                    @include('backend.parts.modal_delete' , ['model'=>$faq , 'route' => route('faqs.destroy' , $faq), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $faq->id }}</td>
                                <td>{{ $faq->website?->domain }}</td>
                                <td>@include('common.table_status' , ['model' => $faq])</td>
                                <td>{{ $faq->slug }}</td>
                                <td>{{ $faq->question }}</td>
                                <td class="mw-400px text-truncate">{{ $faq->answer }}</td>
                                <td>
                                    @foreach($faq->tagsWithType('faq_tag') as $faqTag)
                                    <span class="badge badge-outline badge-primary">{{ $faqTag->name }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $faq->label }}</td>
                                <td>{{ $faq->remark }}</td>
                                <td>{{ $faq->order }}</td>
                                <td>{{ $faq->is_pinned }}</td>
                                <td>{{ $faq->created_at }}</td>

                                @if(request()->show_detail)
                                <td>{{ $faq->updated_at }}</td>
                                @endif
                                
                            <tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $faqs])

    {{-- Pagination --}}
    {{-- <div class="mt-5">
        {{ $faqs->withQueryString()->links() }}
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