<div class="card-body border-top p-3 p-md-9">

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['pending', 'paid', 'failed', 'canceled', 'completed'] as $status)
                    <option value="{{ $status }}" {{ $status === old('status', $order->status ?? 'pending') ? 'selected' : '' }}>
                        @lang('wncms::word.' . $status)
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- User --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('wncms::word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user_id" name="user_id" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}" {{ $user->id === old('user_id', $order->user_id ?? null) ? 'selected' : '' }}>
                        {{ $user->username }} (#{{ $user->id }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Total Amount --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="total_amount">@lang('wncms::word.total_amount')</label>
        <div class="col-lg-9 fv-row">
            <input id="total_amount" type="number" name="total_amount" class="form-control form-control-sm" step="0.01" value="{{ old('total_amount', $order->total_amount ?? null) }}" required />
        </div>
    </div>

    {{-- Payment Method --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="payment_method">@lang('wncms::word.payment_method')</label>
        <div class="col-lg-9 fv-row">
            <input id="payment_method" type="text" name="payment_method" class="form-control form-control-sm" value="{{ old('payment_method', $order->payment_method ?? null) }}" placeholder="@lang('wncms::word.optional')" />
        </div>
    </div>

    {{-- Slug (read-only) --}}
    @if(isset($order) && $order->slug)
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
            <div class="col-lg-9 fv-row">
                <input id="slug" type="text" class="form-control form-control-sm" value="{{ $order->slug }}" disabled />
            </div>
        </div>
    @endif

    {{-- Created At --}}
    @if(isset($order) && $order->created_at)
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="created_at">@lang('wncms::word.created_at')</label>
            <div class="col-lg-9 fv-row">
                <input id="created_at" type="text" class="form-control form-control-sm" value="{{ $order->created_at->format('Y-m-d H:i:s') }}" disabled />
            </div>
        </div>
    @endif

    {{-- Updated At --}}
    @if(isset($order) && $order->updated_at)
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="updated_at">@lang('wncms::word.updated_at')</label>
            <div class="col-lg-9 fv-row">
                <input id="updated_at" type="text" class="form-control form-control-sm" value="{{ $order->updated_at->format('Y-m-d H:i:s') }}" disabled />
            </div>
        </div>
    @endif

</div>
