@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('products.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Filter by Type --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select_type')</option>
                        <option value="virtual" @if(request('type') == 'virtual') selected @endif>@lang('wncms::word.virtual')</option>
                        <option value="physical" @if(request('type') == 'physical') selected @endif>@lang('wncms::word.physical')</option>
                    </select>
                </div>

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
            {{-- Create + Bulk Create + Clone + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'products',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $products])

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
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms::word.type')</th>
                            <th>@lang('wncms::word.price')</th>
                            <th>@lang('wncms::word.stock')</th>
                            <th>@lang('wncms::word.created_at')</th>

                            @if(request()->show_detail)
                            <th>@lang('wncms::word.updated_at')</th>
                            @endif
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($products as $product)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $product->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('products.edit', $product) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $product, 'route' => route('products.destroy', $product), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $product->id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>@lang('wncms::word.' . $product->type)</td>
                                <td>{{ $product->price }}</td>
                                <td>{{ $product->stock ?? '-' }}</td>
                                <td>{{ $product->created_at }}</td>

                                @if(request()->show_detail)
                                <td>{{ $product->updated_at }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $products])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $products->withQueryString()->links() }}
    </div>

@endsection

@push('foot_js')
    <script>
        // Submit form on checkbox change
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        });
    </script>
@endpush
