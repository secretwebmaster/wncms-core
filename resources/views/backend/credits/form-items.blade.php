<div class="card-body border-top p-3 p-md-9">

    {{-- User --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.user')</label>
        <div class="col-lg-9 fv-row">
            <select id="user_id" name="user_id" class="form-select form-select-sm" @required(request()->routeIs('credits.create')) @disabled(request()->routeIs('credits.edit'))>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ $user->id == (old('user_id') ?? $credit->user_id ?? request()->user_id ?? null) ? 'selected' : '' }}>{{ $user->username }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Credit Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" @required(request()->routeIs('credits.create')) @disabled(request()->routeIs('credits.edit'))>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($types as $type)
                <option value="{{ $type }}" {{ $type==(old('type') ?? $credit->type ?? request()->type) ? 'selected' : '' }}>
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
            <input id="amount" type="number" name="amount" class="form-control form-control-sm" step="0.01" value="{{ old('amount', $credit->amount ?? null) }}" required />
        </div>
    </div>

</div>