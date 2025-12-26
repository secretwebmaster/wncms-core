<div class="card-body border-top p-3 p-md-9">
    {{-- status --}}
    @if(!empty($statuses))
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($statuses ?? [] as $status)
                    <option  value="{{ $status }}" {{ $status === old('status', $channel->status ?? 'active') ? 'selected' :'' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    @foreach([
        'name',
        'slug',
        'contact',
    ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('wncms::word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $channel->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- remark --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="remark">@lang('wncms::word.remark')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="remark" name="remark" class="form-control" rows="10">{{ $channel->remark ?? '' }}</textarea>
        </div>
    </div>
</div>

@include('wncms::backend.common.developer-hints')