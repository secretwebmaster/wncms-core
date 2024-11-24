<div class="card-body border-top p-3 p-md-9">
    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $plan->name ?? null) }}" required />
        </div>
    </div>

    {{-- Slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $plan->slug ?? null) }}" required />
        </div>
    </div>

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach($statuses as $status)

                <option value="{{ $status }}" {{ $status===old('status', $plan->status ?? 'active') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="description">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="description" name="description" class="form-control form-control-sm" rows="3">{{ old('description', $plan->description ?? null) }}</textarea>
        </div>
    </div>

    {{-- free_trial_duration --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.free_trial_duration')</label>
        <div class="col-lg-9 fv-row">
            <input id="free_trial_duration" type="number" name="free_trial_duration" class="form-control form-control-sm" value="{{ old('free_trial_duration', $plan->free_trial_duration ?? null) }}" required />
        </div>
    </div>

    {{-- Billing Cycles and Prices --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.prices')</label>
        <div class="col-lg-9 fv-row">
            <div id="priceRepeater">
                @foreach(old('prices', $plan->prices ?? [['duration' => '', 'duration_unit' => '', 'price' => '', 'is_lifetime' => false]]) as $key => $price)
                <div class="repeater-item d-flex mb-2 align-items-center">
                    {{-- Duration --}}
                    <input type="number" name="prices[{{ $key }}][duration]" class="form-control form-control-sm me-2 w-auto" placeholder="@lang('wncms::word.duration')" value="{{ old(" prices.{$key}.duration", $price['duration'] ?? null) }}" />

                    {{-- Duration Unit --}}
                    <select name="prices[{{ $key }}][duration_unit]" class="form-select form-select-sm me-2 w-auto">
                        <option value="">@lang('wncms::word.please_select')</option>
                        @foreach(['day', 'week', 'month', 'year'] as $unit)
                        <option value="{{ $unit }}" {{ $unit===old("prices.{$key}.duration_unit", $price['duration_unit'] ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $unit)</option>
                        @endforeach
                    </select>

                    {{-- amount --}}
                    <input type="number" name="prices[{{ $key }}][amount]" class="form-control form-control-sm me-2 w-auto" step="0.01" placeholder="@lang('wncms::word.price')" value="{{ old(" prices.{$key}.amount", $price['amount'] ?? null) }}" required />

                    {{-- Lifetime --}}
                    <div class="form-check form-check-sm form-check-custom form-switch">
                        <input type="hidden" name="prices[{{ $key }}][is_lifetime]" value="0">
                        <input type="checkbox" class="form-check-input form-check-sm" name="prices[{{ $key }}][is_lifetime]" value="1" {{ old("prices.{$key}.is_lifetime", $price['is_lifetime'] ?? false) ? 'checked' : '' }} />
                        <label class="form-check-label">@lang('wncms::word.is_lifetime')</label>
                    </div>

                    {{-- Remove Button --}}
                    <button type="button" class="btn btn-sm btn-danger ms-2 remove-repeater-item">@lang('wncms::word.remove')</button>
                </div>
                @endforeach
            </div>

            {{-- Add Button --}}
            <button type="button" class="btn btn-sm btn-primary mt-2" id="addRepeaterItem">@lang('wncms::word.add_price')</button>
        </div>
    </div>
</div>

@push('foot_js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const priceRepeater = document.querySelector('#priceRepeater');
        const addRepeaterItem = document.querySelector('#addRepeaterItem');

        let index = priceRepeater.children.length;

        addRepeaterItem.addEventListener('click', () => {
            const repeaterItem = document.createElement('div');
            repeaterItem.classList.add('repeater-item', 'd-flex', 'mb-2', 'align-items-center');
            repeaterItem.innerHTML = `
                <input type="number" name="prices[${index}][duration]" class="form-control form-control-sm me-2 w-auto" placeholder="@lang('wncms::word.duration')" />
                <select name="prices[${index}][duration_unit]" class="form-select form-select-sm me-2 w-auto">
                    <option value="">@lang('wncms::word.please_select')</option>
                    @foreach(['day', 'week', 'month', 'year'] as $unit)
                        <option value="{{ $unit }}">@lang('wncms::word.' . $unit)</option>
                    @endforeach
                </select>
                <input type="number" name="prices[${index}][amount]" class="form-control form-control-sm me-2 w-auto" step="0.01" placeholder="@lang('wncms::word.price')" required />
                <div class="form-check form-check-sm form-check-custom form-switch">
                    <input type="hidden" name="prices[${index}][is_lifetime]" value="0">
                    <input type="checkbox" class="form-check-input form-check-sm" name="prices[${index}][is_lifetime]" value="1" />
                    <label class="form-check-label">@lang('wncms::word.is_lifetime')</label>
                </div>
                <button type="button" class="btn btn-sm btn-danger ms-2 remove-repeater-item">@lang('wncms::word.remove')</button>
            `;
            priceRepeater.appendChild(repeaterItem);
            index++;
        });

        priceRepeater.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-repeater-item')) {
                e.target.closest('.repeater-item').remove();
            }
        });
    });
</script>
@endpush