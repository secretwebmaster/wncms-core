<div class="card-body border-top p-3 p-md-9">
    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['active', 'inactive'] as $status)
                <option value="{{ $status }}" {{ $status===old('status', $paymentGateway->status ?? 'active') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $paymentGateway->name ?? '') }}" required />
        </div>
    </div>

    {{-- Slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $paymentGateway->slug ?? '') }}" required />
        </div>
    </div>

    {{-- Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="type">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['redirect', 'inline'] as $type)
                <option value="{{ $type }}" {{ $type===old('type', $paymentGateway->type ?? '') ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Account ID --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="account_id">@lang('wncms::word.account_id')</label>
        <div class="col-lg-9 fv-row">
            <input id="account_id" type="text" name="account_id" class="form-control form-control-sm" value="{{ old('account_id', $paymentGateway->account_id ?? '') }}" />
        </div>
    </div>

    {{-- Client ID --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="client_id">@lang('wncms::word.client_id')</label>
        <div class="col-lg-9 fv-row">
            <input id="client_id" type="text" name="client_id" class="form-control form-control-sm" value="{{ old('client_id', $paymentGateway->client_id ?? '') }}" />
        </div>
    </div>

    {{-- Client Secret --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="client_secret">@lang('wncms::word.client_secret')</label>
        <div class="col-lg-9 fv-row">
            <input id="client_secret" type="password" name="client_secret" class="form-control form-control-sm" value="{{ old('client_secret', $paymentGateway->client_secret ?? '') }}" />
        </div>
    </div>

    {{-- Endpoint --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="endpoint">@lang('wncms::word.endpoint')</label>
        <div class="col-lg-9 fv-row">
            <input id="endpoint" type="text" name="endpoint" class="form-control form-control-sm" value="{{ old('endpoint', $paymentGateway->endpoint ?? '') }}" />
        </div>
    </div>

    {{-- Description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="description">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="description" name="description" class="form-control" rows="5">{{ old('description', $paymentGateway->description ?? '') }}</textarea>
        </div>
    </div>

    {{-- Attributes (Key-Value Pairs) --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.attributes')</label>
        <div class="col-lg-9 fv-row" id="attributes-container">
            @php
                $attributes = old('attributes', $paymentGateway->attributes ?? []);
            @endphp
    
            @foreach ($attributes as $index => $attribute)
            <div class="d-flex align-items-center mb-2 attribute-row">
                <input type="text" name="attributes[{{ $index }}][key]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.key')" value="{{ $attribute['key'] ?? '' }}" />
                <input type="text" name="attributes[{{ $index }}][value]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')" value="{{ $attribute['value'] ?? '' }}" />
                <div class="quick-buttons">
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[order_id]">[order_id]</a>
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[user_id]">[user_id]</a>
                </div>
                <button type="button" class="btn btn-sm btn-danger btn-remove-attribute ms-2 text-nowrap">@lang('wncms::word.remove')</button>
            </div>
            @endforeach
    
            <div class="d-flex align-items-center mb-2 attribute-row template d-none">
                <input type="text" name="attributes[__INDEX__][key]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.key')" />
                <input type="text" name="attributes[__INDEX__][value]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')" />
                <div class="quick-buttons">
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[order_id]">[order_id]</a>
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[user_id]">[user_id]</a>
                </div>
                <button type="button" class="btn btn-sm btn-danger btn-remove-attribute ms-2 text-nowrap">@lang('wncms::word.remove')</button>
            </div>

            <button type="button" class="btn btn-sm btn-secondary mt-3" id="btn-add-attribute">@lang('wncms::word.add_attribute')</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('attributes-container');
            const template = container.querySelector('.template');
            let rowIndex = {{ count($attributes ?? []) }}; // Start with existing attributes count
    
            // Add new attribute row
            document.getElementById('btn-add-attribute').addEventListener('click', function () {
                const newRow = template.cloneNode(true);
                newRow.classList.remove('template', 'd-none');
                newRow.innerHTML = newRow.innerHTML.replace(/__INDEX__/g, rowIndex++);
                container.appendChild(newRow);
            });
    
            // Remove attribute row
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-attribute')) {
                    e.target.closest('.attribute-row').remove();
                }
            });
    
            // Set quick value for the field
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-quick-value')) {
                    e.preventDefault();
                    const input = e.target.closest('.attribute-row').querySelector('input[name*="[value]"]');
                    input.value = e.target.dataset.value;
                }
            });
        });
    </script>
    


</div>