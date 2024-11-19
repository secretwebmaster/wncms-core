@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('cards.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Filter by Type --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.type')</option>
                        @foreach(['credit', 'plan', 'product'] as $type)
                            <option value="{{ $type }}" @if($type == request()->type) selected @endif>@lang('wncms::word.' . $type)</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter by Status --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select')@lang('wncms::word.status')</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @if($status == request()->status) selected @endif>@lang('wncms::word.' . $status)</option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                </div>
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">

            <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                @lang('wncms::word.bulk_create')
            </button>
            <div class="modal fade" id="bulkCreateModal" tabindex="-1" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('cards.bulk_create') }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="bulkCreateModalLabel">@lang('wncms::word.bulk_create')</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                {{-- Type --}}
                                <div class="mb-3">
                                    <label for="type" class="form-label">@lang('wncms::word.type')</label>
                                    <select name="type" id="type" class="form-select" required>
                                        <option value="credit">@lang('wncms::word.credit')</option>
                                        <option value="plan">@lang('wncms::word.plan')</option>
                                        <option value="product">@lang('wncms::word.product')</option>
                                    </select>
                                </div>

                                {{-- Amount --}}
                                <div class="mb-3">
                                    <label for="amount" class="form-label">@lang('wncms::word.amount')</label>
                                    <input type="number" name="amount" id="amount" class="form-control" min="1" max="1000" required>
                                </div>
            
                                {{-- Value --}}
                                <div class="mb-3">
                                    <label for="value" class="form-label">@lang('wncms::word.value')</label>
                                    <input type="number" name="value" id="value" class="form-control" step="0.01" min="0">
                                </div>
            
                                {{-- Plan --}}
                                <div class="mb-3 d-none" id="plan_field">
                                    <label for="plan_id" class="form-label">@lang('wncms::word.plan')</label>
                                    <select name="plan_id" id="plan_id" class="form-select">
                                        <option value="">@lang('wncms::word.select')</option>
                                        @foreach(\Wncms\Models\Plan::all() as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }} #{{$plan->id}}</option>
                                        @endforeach
                                    </select>
                                </div>
            
                                {{-- Product --}}
                                <div class="mb-3 d-none" id="product_field">
                                    <label for="product_id" class="form-label">@lang('wncms::word.product')</label>
                                    <select name="product_id" id="product_id" class="form-select">
                                        <option value="">@lang('wncms::word.select')</option>
                                        @foreach(\Wncms\Models\Product::all() as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} #{{$product->id}}</option>
                                        @endforeach
                                    </select>
                                </div>
            

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('wncms::word.cancel')</button>
                                <button type="submit" class="btn btn-primary">@lang('wncms::word.submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const typeSelect = document.getElementById('type');
                    const planField = document.getElementById('plan_field');
                    const productField = document.getElementById('product_field');
            
                    typeSelect.addEventListener('change', function () {
                        // Hide all conditional fields
                        planField.classList.add('d-none');
                        productField.classList.add('d-none');
            
                        // Show the corresponding field based on selected type
                        if (typeSelect.value === 'plan') {
                            planField.classList.remove('d-none');
                        } else if (typeSelect.value === 'product') {
                            productField.classList.remove('d-none');
                        }
                    });
                });
            </script>
            
            
            
            @include('wncms::backend.common.default_toolbar_buttons', ['model_prefix' => 'cards'])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $cards])

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
                            <th>@lang('wncms::word.code')</th>
                            <th>@lang('wncms::word.type')</th>
                            <th>@lang('wncms::word.value')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.user_id')</th>
                            <th>@lang('wncms::word.plan_id')</th>
                            <th>@lang('wncms::word.created_at')</th>

                            @if(request()->show_detail)
                                <th>@lang('wncms::word.redeemed_at')</th>
                                <th>@lang('wncms::word.expired_at')</th>
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($cards as $card)
                            <tr>
                                {{-- Checkbox --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="{{ $card->id }}" data-model-id="{{ $card->id }}"/>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('cards.edit', $card) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $card, 'route' => route('cards.destroy', $card), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $card->id }}</td>
                                <td>{{ $card->code }}</td>
                                <td>@lang('wncms::word.' . $card->type)</td>
                                <td>{{ $card->value ?? '-' }}</td>
                                <td>@lang('wncms::word.' . $card->status)</td>
                                <td>{{ $card->user?->username ?? '-' }}</td>
                                <td>{{ $card->plan?->name ?? '-' }}</td>
                                <td>{{ $card->created_at->format('Y-m-d H:i') }}</td>

                                @if(request()->show_detail)
                                    <td>{{ $card->redeemed_at ? $card->redeemed_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>{{ $card->expired_at ? $card->expired_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>{{ $card->updated_at->format('Y-m-d H:i') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    @include('wncms::backend.common.showing_item_of_total', ['models' => $cards])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $cards->withQueryString()->links() }}
    </div>

@endsection
