<div class="card-body border-top p-3 p-md-9">
    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $discount->name ?? null) }}" required/>
        </div>
    </div>

    {{-- Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['percentage', 'fixed'] as $type)
                    <option value="{{ $type }}" {{ $type === old('type', $discount->type ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Value --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="value">@lang('wncms::word.value')</label>
        <div class="col-lg-9 fv-row">
            <input id="value" type="number" name="value" class="form-control form-control-sm" step="0.01" value="{{ old('value', $discount->value ?? null) }}" required/>
        </div>
    </div>

    {{-- Start At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="started_at">@lang('wncms::word.started_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="started_at" type="datetime-local" name="started_at" class="form-control form-control-sm" value="{{ old('started_at', $discount->started_at ? $discount->started_at->format('Y-m-d\TH:i') : null) }}"/>
        </div>
    </div>

    {{-- End At --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="ended_at">@lang('wncms::word.ended_at')</label>
        <div class="col-lg-9 fv-row">
            <input id="ended_at" type="datetime-local" name="ended_at" class="form-control form-control-sm" value="{{ old('ended_at', $discount->ended_at ? $discount->ended_at->format('Y-m-d\TH:i') : null) }}"/>
        </div>
    </div>
</div>
