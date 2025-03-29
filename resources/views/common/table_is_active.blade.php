@if(((object)$model)->{$active_column ?? 'is_active'} ?? false)
<span class="@if(!empty($badgeStyle)) badge badge-success @else text-success fw-bold @endif">@lang('wncms::word.yes')</span>
@else
<span class="@if(!empty($badgeStyle)) badge badge-danger @else text-danger fw-bold @endif">@lang('wncms::word.no')</span>
@endif