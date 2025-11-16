@php
    $raw = $model->{$column};

    if (empty($raw)) {
        $value = null;
    } elseif ($raw instanceof \Carbon\Carbon) {
        $value = $raw;
    } else {
        $value = \Carbon\Carbon::parse($raw);
    }
@endphp


@if (is_null($value))
    <span class="text-muted"></span>

@elseif ($column == 'expired_at')
    @if ($value->lt(now()))
        <span class="text-danger fw-bold">{{ $value }}</span>
    @elseif ($value->between(now(), now()->addDays($warning_days ?? 3)))
        <span class="text-warning fw-bold">{{ $value }}</span>
    @else
        <span>{{ $value }}</span>
    @endif

@else
    @if ($value->isToday())
        <span class="text-danger fw-bold">{{ $value }}</span>
    @elseif ($value->gt(now()->subDays($warning_days ?? 3)))
        <span class="text-warning fw-bold">{{ $value }}</span>
    @else
        <span>{{ $value }}</span>
    @endif
@endif
