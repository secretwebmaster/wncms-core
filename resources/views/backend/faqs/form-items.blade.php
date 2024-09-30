<div class="card-body border-top p-3 p-md-9">
    {{-- status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('word.please_select')</option>
                @foreach($statuses as $status)
                    <option  value="{{ $status }}" {{ $status === old('status', $faq->status ?? 'active') ? 'selected' :'' }}>@lang('word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>
    
    {{-- website --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label  fw-bold fs-6">@lang('word.website')</label>
        <div class="col-lg-9 fv-row">
            <select id="website_id" name="website_id" class="form-select form-select-sm" required>
                <option value=""@lang('word.please_select')> @lang('word.website')</option>

                @foreach($websites ?? [] as $_website)
                    <option  value="{{ $_website->id }}" {{ $_website->id === old('website_id', $faq->website?->id ?? $website->id ?? null) ? 'selected' :'' }}>{{ $_website->domain }} #{{ $_website->id }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="slug">@lang('word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', (!empty($isCloning) ? wncms()->getUniqueSLug('faqs') : $faq->slug ?? null)) }}"/>
        </div>
    </div>

    @foreach([
        'question',
        'answer',
        'label',
        'remark',
    ] as $field)
        {{-- text_example --}}
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="{{ $field }}">@lang('word.' . $field)</label>
            <div class="col-lg-9 fv-row">
                <input id="{{ $field }}" type="text" name="{{ $field }}" class="form-control form-control-sm" value="{{ old($field, $faq->{$field} ?? null) }}"/>
            </div>
        </div>
    @endforeach

    {{-- order --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="order">@lang('word.order')</label>
        <div class="col-lg-9 fv-row">
            <input id="order" type="number" name="order" class="form-control form-control-sm" value="{{ old('order', $faq->order ?? null) }}"/>
        </div>
    </div>

    {{-- is_pinned --}}
    <div class="row mb-3">
        <label class="col-3 col-form-label fw-bold fs-6" for="is_pinned">@lang('word.is_pinned')</label>
        <div class="col-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_pinned" type="hidden" name="is_pinned" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_pinned" name="is_pinned" value="1" {{ old('is_pinned', $faq->is_pinned ?? null) ? 'checked' : '' }}/>
                <label class="form-check-label" for="is_pinned"></label>
            </div>
        </div>
    </div>

    {{-- faq_tag --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('word.faq_tag')</label>
        <div class="col-lg-9 fv-row">
            <input id="faq_tags" class="form-control form-control-sm p-0"  name="faq_tags" value="{{ $faq->tagsWithType('faq_tag')->implode('name', ',') }}"/>
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#faq_tags");
                var faq_tags = @json(wncms()->tag()->getTagifyDropdownItems('faq_tag'));
    
                console.log(faq_tags)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: faq_tags,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20,           // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0,             // <- show suggestions on focus
                        closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                        searchKeys: ['name','value'],
                    }
                });
            });
        </script>
    </div>

</div>