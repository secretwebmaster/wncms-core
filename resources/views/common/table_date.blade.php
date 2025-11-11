@php
    $value = $model->{$column} instanceof \Carbon\Carbon
        ? $model->{$column}
        : \Carbon\Carbon::parse($model->{$column});
@endphp

@if($column == 'expired_at')
    @if($value->lt(now()))
        <span class="text-danger fw-bold">{{ $value }}</span>
    @elseif($value->between(now(), now()->addDays($warning_days ?? 3)))
        <span class="text-warning fw-bold">{{ $value }}</span>
    @else
        <span>{{ $value }}</span>
    @endif
@else
    {{-- Today --}}
    @if($value->isToday())
        <span class="text-danger fw-bold">{{ $value }}</span>
    {{-- Recent days --}}
    @elseif($value->gt(now()->subDays($warning_days ?? 3)))
        <span class="text-warning fw-bold">{{ $value }}</span>
    @else
        <span>{{ $value }}</span>
    @endif
@endif
