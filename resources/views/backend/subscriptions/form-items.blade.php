<div class="card-body border-top p-3 p-md-9">
    {{-- User --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user" name="user_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($users ?? [] as $user)
                <option value="{{ $user->id }}" {{ $user->id == old('user_id', $subscription->user_id ?? null) ? 'selected' : '' }}>
                    {{ $user->username }} #{{ $user->id }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- status --}}
    @if(!empty($statuses))
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($statuses ?? [] as $status)
                <option value="{{ $status }}" {{ $status == old('status', $subscription->status ?? 'active') ? 'selected' :'' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif


    {{-- Plan --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.plan')</label>
        <div class="col-lg-9 fv-row">
            <select id="plan" name="plan_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($plans ?? [] as $plan)
                <option value="{{ $plan->id }}" {{ $plan->id == old('plan_id', $subscription->plan_id ?? null) ? 'selected' : '' }}>
                    {{ $plan->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Subscribed At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="subscribed_at">@lang('wncms::word.subscribed_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="subscribed_at" type="datetime-local" name="subscribed_at" class="form-control form-control-sm"
                value="{{ old('subscribed_at', $subscription->subscribed_at ? $subscription->subscribed_at->format('Y-m-d\TH:i') : null) }}" />
        </div>
    </div>

    {{-- Expired At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="expired_at">@lang('wncms::word.expired_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="expired_at" type="datetime-local" name="expired_at" class="form-control form-control-sm"
                value="{{ old('expired_at', $subscription->expired_at ? $subscription->expired_at->format('Y-m-d\TH:i') : null) }}" />
        </div>
    </div>
</div>