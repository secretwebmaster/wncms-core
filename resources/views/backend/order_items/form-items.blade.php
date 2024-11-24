<div class="card-body border-top p-3 p-md-9">

    {{-- Order --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="order_id">@lang('wncms::word.order')</label>
        <div class="col-lg-9 fv-row">
            <select id="order_id" name="order_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($orders ?? [] as $order)
                    <option value="{{ $order->id }}" {{ $order->id === old('order_id', $orderItem->order_id ?? null) ? 'selected' : '' }}>
                        #{{ $order->id }} - {{ $order->slug }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Item Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="order_itemable_type">@lang('wncms::word.order_itemable_type')</label>
        <div class="col-lg-9 fv-row">
            <input id="order_itemable_type" type="text" name="order_itemable_type" class="form-control form-control-sm" value="{{ old('order_itemable_type', $orderItem->order_itemable_type ?? null) }}" required />
        </div>
    </div>

    {{-- Item ID --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="order_itemable_type">@lang('wncms::word.order_itemable_type')</label>
        <div class="col-lg-9 fv-row">
            <input id="order_itemable_type" type="number" name="order_itemable_type" class="form-control form-control-sm" value="{{ old('order_itemable_type', $orderItem->order_itemable_type ?? null) }}" required />
        </div>
    </div>

    {{-- Quantity --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="quantity">@lang('wncms::word.quantity')</label>
        <div class="col-lg-9 fv-row">
            <input id="quantity" type="number" name="quantity" class="form-control form-control-sm" value="{{ old('quantity', $orderItem->quantity ?? 1) }}" min="1" required />
        </div>
    </div>

    {{-- Price --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="price">@lang('wncms::word.price')</label>
        <div class="col-lg-9 fv-row">
            <input id="price" type="number" name="price" class="form-control form-control-sm" step="0.01" value="{{ old('price', $orderItem->price ?? 0) }}" required />
        </div>
    </div>

    {{-- Total --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="total">@lang('wncms::word.total')</label>
        <div class="col-lg-9 fv-row">
            <input id="total" type="number" name="total" class="form-control form-control-sm" step="0.01" value="{{ old('total', $orderItem->total ?? 0) }}" required />
        </div>
    </div>
</div>
