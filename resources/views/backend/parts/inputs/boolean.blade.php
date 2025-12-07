<div class="form-check form-check-solid form-check-custom form-switch">
    <input type="hidden"
        name="{{ $inputName }}"
        value="0">
    <input class="form-check-input w-35px h-20px" type="checkbox" id="{{ $option['name'] }}"
        name="{{ $inputName }}"
        value="1" {{ $currentValue ?? false ? 'checked' : '' }} />
    <label class="form-check-label" for="{{ $option['name'] }}"></label>
</div>