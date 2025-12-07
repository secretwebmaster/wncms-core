@if ($option['options'] == 'pages')

    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
        @if (empty($option['required']))
            <option value="">@lang('wncms::word.please_select')</option>
        @endif
        @foreach (wncms()->page()->getList(['website_id' => $website?->id, 'cache' => false]) as $page)
            <option value="{{ $page->id }}" @selected($currentValue == $page->id)>
                {{ $page->title }}
            </option>
        @endforeach
    </select>
@elseif($option['options'] == 'menus')
    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
        @if (empty($option['required']))
            <option value="">@lang('wncms::word.please_select')</option>
        @endif
        @foreach (wncms()->menu()->getList([], $website?->id) as $menu)
            <option value="{{ $menu->id }}" @if ($currentValue == $menu->id) selected @endif>{{ $menu->name }}</option>
        @endforeach
    </select>
@elseif($option['options'] == 'positions')
    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
        @if (empty($option['required']))
            <option value="">@lang('wncms::word.please_select')</option>
        @endif
        @foreach (\Wncms\Models\Advertisement::POSITIONS ?? [] as $option_key => $option_value)
            <option value="{{ $option_value }}" @if (($currentOptions[$option['name']] ?? '') == $option_value) selected @endif>
                @if (isset($option['translate_option']) && $option['translate_option'] === false)
                    {{ $option_value }}
                @else
                    @lang('wncms::word.' . $option_value)
                @endif
            </option>
        @endforeach
    </select>
@else
    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
        @if (empty($option['required']))
            <option value="">@lang('wncms::word.please_select')</option>
        @endif
        @foreach ($option['options'] ?? [] as $option_key => $option_value)
            <option value="{{ $option_value }}" @if (($currentOptions[$option['name']] ?? '') == $option_value) selected @endif>
                @if (isset($option['translate_option']) && $option['translate_option'] === false)
                    {{ $option_value }}
                @else
                    @lang($themeId . '::word.' . $option_value)
                @endif
            </option>
        @endforeach
    </select>

@endif