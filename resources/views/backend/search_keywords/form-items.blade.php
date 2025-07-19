<div class="card-body border-top p-3 p-md-9">
    @foreach([
        'keyword',
        'locale',
    ] as $field)
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('wncms::word.' . $field)</label>
        <div class="col-lg-9 fv-row">
            <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $search_keyword->{$field} ?? null) }}" />
        </div>
    </div>
    @endforeach

    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="count">@lang('wncms::word.count')</label>
        <div class="col-lg-9 fv-row">
            <input id="count" type="number" name="count" class="form-control form-control-sm" value="{{ old('count', $search_keyword->count ?? null) }}" />
        </div>
    </div>
</div>

