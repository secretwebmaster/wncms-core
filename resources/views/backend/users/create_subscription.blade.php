@extends('layouts.backend')

@section('content')

@include('backend.parts.message')				
                
    <div>
        <form class="d-flex flex-column flex-lg-row" action="{{ route('users.user_orders.store') }}" method="POST">
            @csrf

            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            <input type="hidden" name="type" value="subscription">
            <div class="flex-lg-row-fluid me-lg-15 order-2 order-lg-1 mb-10 mb-lg-0">

                {{-- period --}}
                <div class="card mb-3 border border-1 border-dark">

                    <div class="card-header bg-dark py-2 min-h-25">
                        <div class="card-title">
                            <h2 class="fw-bold text-white">@lang('word.payment_period')</h2>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row" data-kt-buttons="true">
                            @foreach(['monthly','annually'] as $index => $period)
                            <div class="col-12 col-md-6">
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-3 mb-3 @if(request()->period . "ly" == $period || (!request()->period && $index == 0)) active @endif">
                                    <div class="d-flex align-items-center me-2">

                                        <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                            <input class="form-check-input" type="radio" name="period" value="{{ $period }}" @if(request()->period . "ly" == $period || (!request()->period && $index == 0)) checked @endif/>
                                        </div>

                                        {{-- <div>
                                            <img class="w-50px h-50px" src="{{ $payment_gateway->payment_gateway_logo }}" alt="">
                                        </div> --}}

                                        <div class="flex-grow-1">
                                            <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">NT$ {{ $plan->{"price_" . $period} }} / @lang('word.' . $period) @if($period == 'annually') <span class="badge badge-danger ms-2">Save 17%</span> @endif</h2>
                                            <div class="fw-semibold opacity-50">@lang('word.pay_' . $period)</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                            
                        </div>
                    </div>
                </div>

                {{-- Payment method--}}
                <div class="card card-flush  border border-1 border-dark" data-kt-subscriptions-form="pricing">

                    {{-- Header --}}
                    <div class="card-header bg-dark py-2 min-h-25">
                        <div class="card-title">
                            <h2 class="fw-bold text-white">@lang('word.payment_method')</h2>
                        </div>
                    </div>

                    {{-- Payment gateways --}}
                    <div class="card-body">
                        <div class="row" data-kt-buttons="true">
                            
                            @foreach($payment_gateways as $index => $payment_gateway)
                            <div class="col-12 col-md-6">
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-3 mb-3 @if($index == 0) active @endif">
                                    <div class="d-flex align-items-center me-2">

                                        <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                            <input class="form-check-input" type="radio" name="payment_gateway_id" value="{{ $payment_gateway->slug }}" @if($index == 0) checked @endif/>
                                        </div>

                                        <div>
                                            <img class="w-50px h-50px" src="{{ $payment_gateway->payment_gateway_logo }}" alt="">
                                        </div>

                                        <div class="flex-grow-1">
                                            <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap"> {{ $payment_gateway->name }}</h2>
                                            <div class="fw-semibold opacity-50">{{ $payment_gateway->description }}</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                            
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar--}}
            <div class="flex-column flex-lg-row-auto w-100 w-lg-250px w-xl-300px mb-10 order-1 order-lg-2">
                <div class="card card-flush pt-3 mb-0 border border-1 border-dark shadow-sm" data-kt-sticky="true" data-kt-sticky-name="subscription-summary" data-kt-sticky-offset="{default: false, lg: '200px'}" data-kt-sticky-width="{lg: '250px', xl: '300px'}" data-kt-sticky-left="auto" data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('word.order_detail')</h2>
                        </div>
                    </div>



                    <div class="card-body pt-0 fs-6">

                        <div class="separator separator-dashed mb-7"></div>
                        
                        {{-- User info --}}
                        <div class="mb-7">
                            <h5 class="mb-3">@lang('word.user_info')</h5>
                            <div class="d-flex align-items-center mb-1">
                                <span class="fw-bold text-gray-800 text-hover-primary me-2">{{ auth()->user()->username }}</span>
                                <span class="badge badge-light-success">@lang('word.verified')</span>
                            </div>

                            <span class="fw-semibold text-gray-600 text-hover-primary">{{ auth()->user()->email }}</span>
                        </div>
                        
                        <div class="separator separator-dashed mb-7"></div>

                        {{-- Plan --}}
                        <div class="mb-7">
                            <h5 class="mb-3">@lang('word.plan_info')</h5>
                            <div class="mb-0">
                                <ul>
                                    <li><span class="text-info fw-bold">{{ $plan->name }}</span></li>
                                    <li>@lang('word.monthly_word_limit'): <span class="fw-bold text-info text_monthly_word_limit">{{ $plan->monthly_word_limit }}</span></li>
                                    <li>@lang('word.monthly_document_limit'): <span class="fw-bold text-info text_monthly_document_limit">{{ $plan->monthly_document_limit == -1 ? __('word.unlimited') : $plan->monthly_document_limit }}</span></li>
                                    <li>@lang('word.monthly_image_limit'): <span class="fw-bold text-info text_monthly_image_limit">{{ $plan->monthly_image_limit }}</span></li>
                                    @foreach($plan->has_features as $feature)
                                    <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>

                            </div>
                        </div>

                        <div class="separator separator-dashed mb-7"></div>

                        {{-- <div class="mb-10">
                            <h5 class="mb-3">Payment Details</h5>
                            <div class="mb-0">
                                <div class="fw-semibold text-gray-600 d-flex align-items-center">Mastercard2
                                <img src="{{ asset('wncms/media/svg/card-logos/mastercard.svg') }}" class="w-35px ms-2" alt="" /></div>

                                <div class="fw-semibold text-gray-600">Expires Dec 2024</div>
                            </div>
                        </div> --}}

                        {{-- Actions--}}
                        <div class="mb-0">
                            <button type="submit" class="btn btn-dark w-100 fw-bold" id="kt_subscriptions_create_button">
                                {{-- Indicator label--}}
                                <span class="indicator-label">@lang('word.confirm_and_pay')</span>

                                {{-- Indicator progress--}}
                                <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>

                    </div>

                </div>

            </div>
        </form>
    </div>

@endsection
