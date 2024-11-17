<div class="card-body border-top p-3 p-md-9">
    {{-- Code --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="code">@lang('wncms::word.code')</label>
        <div class="col-lg-9 fv-row">
            <input id="code" type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $card->code ?? null) }}" required/>
        </div>
    </div>

    {{-- Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['credit', 'plan', 'product'] as $type)
                    <option value="{{ $type }}" {{ $type === old('type', $card->type ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Value --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="value">@lang('wncms::word.value')</label>
        <div class="col-lg-9 fv-row">
            <input id="value" type="number" name="value" class="form-control form-control-sm" step="0.01" value="{{ old('value', $card->value ?? null) }}"/>
        </div>
    </div>

    {{-- Plan --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.plan')</label>
        <div class="col-lg-9 fv-row">
            <select id="plan_id" name="plan_id" class="form-select form-select-sm">
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ $plan->id === old('plan_id', $card->plan_id ?? null) ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- User --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user_id" name="user_id" class="form-select form-select-sm">
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $user->id === old('user_id', $card->user_id ?? null) ? 'selected' : '' }}>{{ $user->username }} #{{ $user->id }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Redeemed At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="redeemed_at">@lang('wncms::word.redeemed_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="redeemed_at" type="datetime-local" name="redeemed_at" class="form-control form-control-sm" value="{{ old('redeemed_at', $card->redeemed_at ? $card->redeemed_at->format('Y-m-d\TH:i') : null) }}"/>
        </div>
    </div>

    {{-- Expired At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="expired_at">@lang('wncms::word.expired_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="expired_at" type="datetime-local" name="expired_at" class="form-control form-control-sm" value="{{ old('expired_at', $card->expired_at ? $card->expired_at->format('Y-m-d\TH:i') : null) }}"/>
        </div>
    </div>

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['active', 'redeemed', 'expired'] as $status)
                    <option value="{{ $status }}" {{ $status === old('status', $card->status ?? 'active') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
