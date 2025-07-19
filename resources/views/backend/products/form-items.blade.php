<div class="card-body border-top p-3 p-md-9">
    {{-- name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $product->name ?? null) }}" required />
        </div>
    </div>

    {{-- slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $product->slug ?? null) }}" required />
        </div>
    </div>

    {{-- type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['virtual', 'physical'] as $type)
                <option value="{{ $type }}" {{ $type===old('type', $product->type ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- price --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="price">@lang('wncms::word.price')</label>
        <div class="col-lg-9 fv-row">
            <input id="price" type="number" name="price" class="form-control form-control-sm" step="0.01" value="{{ old('price', $product->price ?? null) }}" required />
        </div>
    </div>

    {{-- stock --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="stock">@lang('wncms::word.stock')</label>
        <div class="col-lg-9 fv-row">
            <input id="stock" type="number" name="stock" class="form-control form-control-sm" value="{{ old('stock', $product->stock ?? null) }}" />
        </div>
    </div>

    {{-- is_variable --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_variable">@lang('wncms::word.is_variable')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_variable" type="hidden" name="is_variable" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_variable" name="is_variable" value="1" {{ old('is_variable', $product->is_variable ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_variable"></label>
            </div>
        </div>
    </div>

    {{-- attribute --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">
            @lang('wncms::word.properties')
        </label>
        <div class="col-lg-9">
            <div class="repeater" id="propertyRepeater">
                <div data-repeater-list="properties">
                    {{-- At least one empty item is needed for clone template --}}
                    @if(empty(old('properties', $product->properties ?? [])))
                    <div data-repeater-item class="d-flex align-items-center mb-2">
                        <input type="text" name="name" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.name')">
                        <input type="text" name="value" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')">
                        <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                    </div>
                    @else
                    @foreach(old('properties', $product->properties ?? []) as $item)
                    <div data-repeater-item class="d-flex align-items-center mb-2">
                        <input type="text" name="name" class="form-control form-control-sm me-2" value="{{ $item['name'] ?? '' }}" placeholder="@lang('wncms::word.name')">
                        <input type="text" name="value" class="form-control form-control-sm me-2" value="{{ $item['value'] ?? '' }}" placeholder="@lang('wncms::word.value')">
                        <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                    </div>
                    @endforeach
                    @endif
                </div>
                <button data-repeater-create type="button" class="btn btn-sm btn-primary mt-2">@lang('wncms::word.add_item')</button>
            </div>
        </div>
    </div>

    {{-- variants --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">
            @lang('wncms::word.variants')
        </label>
        <div class="col-lg-9">
            <div class="repeater" id="variantRepeater">
                <div data-repeater-list="variants">
                    @if(empty(old('variants', $product->variants ?? [])))
                    <div data-repeater-item class="d-flex align-items-center mb-2">
                        <input type="text" name="name" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.name')">
                        <input type="text" name="value" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')">
                        <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                    </div>
                    @else
                    @foreach(old('variants', $product->variants ?? []) as $item)
                    <div data-repeater-item class="d-flex align-items-center mb-2">
                        <input type="text" name="name" class="form-control form-control-sm me-2" value="{{ $item['name'] ?? '' }}" placeholder="@lang('wncms::word.name')">
                        <input type="text" name="value" class="form-control form-control-sm me-2" value="{{ $item['value'] ?? '' }}" placeholder="@lang('wncms::word.value')">
                        <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                    </div>
                    @endforeach
                    @endif
                </div>
                <button data-repeater-create type="button" class="btn btn-sm btn-primary mt-2">@lang('wncms::word.add_item')</button>
            </div>
        </div>
    </div>
</div>

@push('foot_js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    <script>
        $(function () {
            $('#propertyRepeater').repeater({
                initEmpty: {{ empty($product->properties) ? 'true' : 'false' }},
                defaultValues: { text: '', number: '' },
                show: function () {
                    $(this).slideDown();
                },
                hide: function (deleteElement) {
                    if (confirm("@lang('wncms::word.confirm_delete_item')")) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

            $('#variantRepeater').repeater({
                initEmpty: {{ empty($product->variants) ? 'true' : 'false' }},
                defaultValues: { text: '', number: '' },
                show: function () {
                    $(this).slideDown();
                },
                hide: function (deleteElement) {
                    if (confirm("@lang('wncms::word.confirm_delete_item')")) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });
        });
    </script>
@endpush