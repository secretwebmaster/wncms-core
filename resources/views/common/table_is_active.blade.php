@if(((object)$model)->{$active_column ?? 'is_active'} ?? false)
<span class="badge badge-success">@lang('wncms::word.yes')</span>
@else
<span class="badge badge-danger">@lang('wncms::word.no')</span>
@endif