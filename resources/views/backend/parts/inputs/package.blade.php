{{-- @elseif($option['options'] == 'contact_forms')
<select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
    @if (empty($option['required']))
        <option value="">@lang('wncms::word.please_select')</option>
    @endif
    @foreach ($wncms->contact_form()->getList() as $contact_form)
        <option value="{{ $contact_form->id }}" @if ($currentValue == $contact_form->id) selected @endif>{{ $contact_form->name }}</option>
    @endforeach
</select> --}}