<div class="card-body border-top p-3 p-md-9">
    @foreach([
        'name',
        'key',
    ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('wncms::word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $parameter->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- remark --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="remark">@lang('wncms::word.remark')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="remark" name="remark" class="form-control" rows="10">{{ $parameter->remark ?? '' }}</textarea>
        </div>
    </div>
</div>