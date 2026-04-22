@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h1 class="mb-4 text-2xl font-semibold text-slate-900">@lang('wncms::word.our_plans')</h1>

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach($plans as $plan)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $plan->description }}</p>

                <div class="table-container mt-3">
                    <table>
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
                </div>

                <a href="{{ route('frontend.plans.show', ['slug' => $plan->slug]) }}" class="mt-3 inline-flex rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">@lang('wncms::word.view_details')</a>
            </div>
            @endforeach
        </div>
    </div>
</main>
@endsection
