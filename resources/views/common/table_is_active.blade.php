@if(((object)$model)->{$active_column ?? 'is_active'} ?? false)
<span class="badge badge-success">@lang('word.yes')</span>
@else
<span class="badge badge-danger">@lang('word.no')</span>
@endif