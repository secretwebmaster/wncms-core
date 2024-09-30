@if($column == 'expired_at')

    @if($model->{$column} < now())
        <span class="text-danger fw-bold">{{ $model->{$column} }}</span>
    @elseif($model->{$column} > now() && $model->{$column} < now()->addDays($warning_days ?? 3))
        <span class="text-warning fw-bold">{{ $model->{$column} }}</span>
    @else
        <span class="">{{ $model->{$column} }}</span>
    @endif

@else
    {{-- Today --}}
    @if(\Carbon\Carbon::parse($model->{$column})->isToday())
    
        <span class="text-danger fw-bold">{{ $model->{$column} }}</span>

    {{-- Recent days--}}
    @elseif($model->{$column} > now()->subDays($warning_days ?? 3))
        <span class="text-warning fw-bold">{{ $model->{$column} }}</span>
    @else
        <span class="">{{ $model->{$column} }}</span>
    @endif

@endif

