<div class="card-body border-top p-3 p-md-9">
    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $product->name ?? null) }}" required/>
        </div>
    </div>

    {{-- Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['virtual', 'physical'] as $type)
                    <option value="{{ $type }}" {{ $type === old('type', $product->type ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Price --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="price">@lang('wncms::word.price')</label>
        <div class="col-lg-9 fv-row">
            <input id="price" type="number" name="price" class="form-control form-control-sm" step="0.01" value="{{ old('price', $product->price ?? null) }}" required/>
        </div>
    </div>

    {{-- Stock --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="stock">@lang('wncms::word.stock')</label>
        <div class="col-lg-9 fv-row">
            <input id="stock" type="number" name="stock" class="form-control form-control-sm" value="{{ old('stock', $product->stock ?? null) }}"/>
        </div>
    </div>
</div>
