@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="card" id="kt_pricing">
    {{-- Card body --}}
    <div class="card-body p-lg-17">
        {{-- Plans --}}
        <div class="d-flex flex-column">
            {{-- Heading --}}
            <div class="mb-13 text-center">
                <h1 class="fs-2hx fw-bold mb-3">升級您的帳戶</h1>
                <div class="text-gray-600 fw-bold fs-2">選擇最適合你業務的優惠方案 <span class="link-danger fw-bold">年費優惠最高達30% off</span></div>
            </div>

            {{-- Nav group --}}
            <div class="nav-group nav-group-outline border border-2 border-dark mx-auto mb-10" data-kt-buttons="true">
                <button class="btn btn-lg btn-color-gray-400 btn-active btn-active-dark fw-bold px-6 py-3 me-2 active" data-kt-plan="month">@lang('word.pay_monthly')</button>
                <button class="btn btn-lg btn-color-gray-400 btn-active btn-active-dark fw-bold px-6 py-3" data-kt-plan="annual">@lang('word.pay_annually')</button>
            </div>

            {{-- plans --}}
            <div class="row g-10">
                @foreach($plans->sortByDesc('sort') as $plan)
                    <div class="col-xl-4">
                        <form class="form_user_upgrade" action="{{ route('users.subscriptions.create', ['plan' => $plan]) }}">
                            <div class="rounded shadow border border-hover border border-3 border-dark">
                                {{-- Option --}}
                                <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-10 px-10">
                                    {{-- Heading --}}
                                    <div class="mb-7 text-center">
                                        <div>
                                            <img class="w-80px h-80px rounded-50" src="{{ $plan->plan_thumbnail }}" alt="">
                                        </div>
                                        {{-- Title --}}
                                        <h1 class="text-dark mb-5 fw-bolder display-6">{{ $plan->name }}</h1>

                                        {{-- Description --}}
                                        <div class="text-gray-500 fw-bold fs-4 fw-semibold mb-5">{{ $plan->description }}
                                        </div>

                                        {{-- Price --}}
                                        <div class="text-center">
                                            <span class="mb-2 text-info">NT$</span>
                                            <span class="fs-3x fw-bold text-info" data-kt-plan-price-month="{{ (int)$plan->price_monthly }}" data-kt-plan-price-annual="{{ (int)($plan->price_annually / 12)}}">{{ (int)$plan->price_monthly }}</span>
                                            <span class="fs-7 fw-semibold opacity-50">/<span data-kt-element="period">@lang('word.per_month')</span></span>
                                        </div>

                                    </div>




                                    <div class="w-100 mb-10">

                                        {{-- Common --}}
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fs-6 text-gray-800 flex-grow-1 pe-3 fw-bold">@lang('word.monthly_word_limit'): {{ $plan->monthly_word_limit }}</span>
                                            {{-- Svg Icon | path: icons/duotune/general/gen043.svg --}}
                                            <span class="svg-icon svg-icon-1 svg-icon-info">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor" />
                                                    <path d="M10.4343 12.4343L8.75 10.75C8.33579 10.3358 7.66421 10.3358 7.25 10.75C6.83579 11.1642 6.83579 11.8358 7.25 12.25L10.2929 15.2929C10.6834 15.6834 11.3166 15.6834 11.7071 15.2929L17.25 9.75C17.6642 9.33579 17.6642 8.66421 17.25 8.25C16.8358 7.83579 16.1642 7.83579 15.75 8.25L11.5657 12.4343C11.2533 12.7467 10.7467 12.7467 10.4343 12.4343Z" fill="currentColor" />
                                                </svg>
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fs-6 text-gray-800 flex-grow-1 pe-3 fw-bold">@lang('word.monthly_image_limit'): {{ $plan->monthly_image_limit }}</span>
                                            {{-- Svg Icon | path: icons/duotune/general/gen043.svg --}}
                                            <span class="svg-icon svg-icon-1 svg-icon-info">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor" />
                                                    <path d="M10.4343 12.4343L8.75 10.75C8.33579 10.3358 7.66421 10.3358 7.25 10.75C6.83579 11.1642 6.83579 11.8358 7.25 12.25L10.2929 15.2929C10.6834 15.6834 11.3166 15.6834 11.7071 15.2929L17.25 9.75C17.6642 9.33579 17.6642 8.66421 17.25 8.25C16.8358 7.83579 16.1642 7.83579 15.75 8.25L11.5657 12.4343C11.2533 12.7467 10.7467 12.7467 10.4343 12.4343Z" fill="currentColor" />
                                                </svg>
                                            </span>
                                        </div>

                                        {{-- Has Features --}}
                                        @foreach(array_filter(explode(",", $plan->has_feature)) as $feature)
                                            <div class="d-flex align-items-center mb-5">
                                                <span class="fs-6 text-gray-800 flex-grow-1 pe-3 fw-bold">{{ $feature }}</span>
                                                {{-- Svg Icon | path: icons/duotune/general/gen043.svg --}}
                                                <span class="svg-icon svg-icon-1 svg-icon-info">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor" />
                                                        <path d="M10.4343 12.4343L8.75 10.75C8.33579 10.3358 7.66421 10.3358 7.25 10.75C6.83579 11.1642 6.83579 11.8358 7.25 12.25L10.2929 15.2929C10.6834 15.6834 11.3166 15.6834 11.7071 15.2929L17.25 9.75C17.6642 9.33579 17.6642 8.66421 17.25 8.25C16.8358 7.83579 16.1642 7.83579 15.75 8.25L11.5657 12.4343C11.2533 12.7467 10.7467 12.7467 10.4343 12.4343Z" fill="currentColor" />
                                                    </svg>
                                                </span>
                                            </div>
                                        @endforeach


                                        {{-- No Features --}}
                                        @foreach(array_filter(explode(",", $plan->no_feature)) as $feature)
                                            <div class="d-flex align-items-center mb-5">
                                                <span class="fs-6 text-gray-500 flex-grow-1 pe-3 fw-bold">{{ $feature }}</span>
                                                {{-- Svg Icon | path: icons/duotune/general/gen043.svg --}}
                                                <span class="svg-icon svg-icon-1">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor" />
                                                    <rect x="7" y="15.3137" width="12" height="2" rx="1" transform="rotate(-45 7 15.3137)" fill="currentColor" />
                                                    <rect x="8.41422" y="7" width="12" height="2" rx="1" transform="rotate(45 8.41422 7)" fill="currentColor" />
                                                </svg>
                                                </span>
                                            </div>
                                        @endforeach

                                    </div>

                                    {{-- Select --}}
                                    @if(auth()->user()->active_subscription?->plan?->id == $plan->id)
                                        <button class="btn btn-lg btn-secondary fw-bold w-100" disabled type="button">@lang('word.current_plan')</button>
                                    @elseif(auth()->user()->active_subscription?->plan?->id != $plan->id && $plan->id == gss('free_plan', 1))
                                        <button class="btn btn-lg btn-secondary fw-bold w-100 btn_submit_user_upgrade" @if($plan->id == gss('free_plan', 1))disabled @endif type="submit">@lang('word.free_plan')</button>
                                    @else
                                        <button class="btn btn-lg btn-dark fw-bold w-100 btn_submit_user_upgrade" type="submit">@lang('word.upgrade_plan')</button>
                                    @endif

                                </div>

                            </div>
                        </form>
                    </div>
                @endforeach


            </div>

        </div>

    </div>

</div>

@endsection

@push('foot_js')
<script src="{{ asset('wncms/js/custom/pages/pricing/general.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.form_user_upgrade').submit(function() {
            var activePeriod = $('[data-kt-plan].active').data('kt-plan');
            $('<input>').attr({
                type: 'hidden',
                name: 'period',
                value: activePeriod
            }).appendTo($(this));
        });
    });
</script>
@endpush