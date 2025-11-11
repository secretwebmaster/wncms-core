@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div class="plans-container">
    <h1 class="plans-title">@lang('wncms::word.our_plans')</h1>

    <div class="plans-grid">
        @foreach($plans as $plan)
        <div class="plan-card">
            <div class="plan-card-body">
                <h3 class="plan-card-title">{{ $plan->name }}</h3>
                <p class="plan-card-text">{{ $plan->description }}</p>

                <table class="plan-table">
                    <thead>
                        <tr>
                            <th>@lang('wncms::word.duration')</th>
                            <th>@lang('wncms::word.price')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->prices as $price)
                        <tr>
                            <td>
                                @if($price->is_lifetime)
                                @lang('wncms::word.lifetime')
                                @else
                                {{ $price->duration }} @lang('wncms::word.' . $price->duration_unit)
                                @endif
                            </td>
                            <td>{{ wncms()->displayPrice($price->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <a href="{{ route('frontend.plans.show', ['slug' => $plan->slug]) }}" class="plan-details-link">
                    @lang('wncms::word.view_details')
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection