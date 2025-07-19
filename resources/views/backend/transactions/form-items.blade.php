<div class="card-body border-top p-3 p-md-9">

    {{-- Order --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.order_id')</label>
        <div class="col-lg-9 fv-row">
            <select id="order" name="order_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($orders ?? [] as $order)
                    <option value="{{ $order->id }}" {{ $order->id === old('order_id', $transaction->order_id ?? null) ? 'selected' : '' }}>#{{ $order->id }} - {{ $order->slug }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['pending', 'paid', 'failed', 'refunded'] as $status)
                    <option value="{{ $status }}" {{ $status === old('status', $transaction->status ?? 'pending') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    
    {{-- Amount --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="amount">@lang('wncms::word.amount')</label>
        <div class="col-lg-9 fv-row">
            <input id="amount" type="number" name="amount" class="form-control form-control-sm" step="0.01" value="{{ old('amount', $transaction->amount ?? null) }}" required/>
        </div>
    </div>

    
    {{-- payment_method --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="payment_method">@lang('wncms::word.payment_method')</label>
        <div class="col-lg-9 fv-row">
            <input id="payment_method" type="text" name="payment_method" class="form-control form-control-sm" value="{{ old('payment_method', $transaction->payment_method ?? null) }}" />
        </div>
    </div>
    
    {{-- ref_id --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="ref_id">@lang('wncms::word.ref_id')</label>
        <div class="col-lg-9 fv-row">
            <input id="ref_id" type="text" name="ref_id" class="form-control form-control-sm" value="{{ old('ref_id', $transaction->ref_id ?? null) }}" />
        </div>
    </div>

    
    {{-- is_fraud --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_fraud">@lang('wncms::word.is_fraud')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_fraud" type="hidden" name="is_fraud" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_fraud" name="is_fraud" value="1" {{ old('is_fraud', $transaction->is_fraud ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_fraud"></label>
            </div>
        </div>
    </div>

</div>