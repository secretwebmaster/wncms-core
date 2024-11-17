<div class="card-body border-top p-3 p-md-9">
    {{-- Order --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.order')</label>
        <div class="col-lg-9 fv-row">
            <select id="order" name="order_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($orders ?? [] as $order)
                    <option value="{{ $order->id }}" {{ $order->id === old('order_id', $transaction->order_id ?? null) ? 'selected' : '' }}>#{{ $order->id }} - {{ $order->slug }}</option>
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
</div>