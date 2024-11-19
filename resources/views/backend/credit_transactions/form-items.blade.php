<div class="card-body border-top p-3 p-md-9">

    {{-- User --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user_id" name="user_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $user->id === old('user_id', $creditTransaction->user_id ?? null) ? 'selected' : '' }}>
                        {{ $user->username }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Credit Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['balance', 'points'] as $type)
                    <option value="{{ $type }}" {{ $type === old('type', $creditTransaction->type ?? null) ? 'selected' : '' }}>
                        @lang('wncms::word.' . $type)
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Transaction Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.transaction_type')</label>
        <div class="col-lg-9 fv-row">
            <select id="transaction_type" name="transaction_type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['earn', 'spend', 'recharge', 'refund', 'adjustment'] as $type)
                    <option value="{{ $type }}" {{ $type === old('transaction_type', $creditTransaction->transaction_type ?? null) ? 'selected' : '' }}>
                        @lang('wncms::word.' . $type)
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Amount --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="amount">@lang('wncms::word.amount')</label>
        <div class="col-lg-9 fv-row">
            <input id="amount" type="number" name="amount" class="form-control form-control-sm" step="0.01" value="{{ old('amount', $creditTransaction->amount ?? null) }}" required/>
        </div>
    </div>

    {{-- Remark --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="remark">@lang('wncms::word.remark')</label>
        <div class="col-lg-9 fv-row">
            <input id="remark" type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $creditTransaction->remark ?? null) }}" />
        </div>
    </div>

</div>
