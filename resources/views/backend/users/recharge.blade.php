@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

<div class="row justify-content-center">
    <div class="col-12 col-md-5">

        <div class="card card-bordered mb-5 mb-xl-10 ">

            {{-- Card header --}}
            <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
                {{-- Card title --}}
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-100">@lang('word.user_recharge')</span>
                    <span class="text-gray-400 pt-1 fw-bold fs-6">@lang('word.you_can_buy_extra_credit')</span>
                </h3>
            </div>



            {{-- Form --}}
            <form action="{{ route('users.user_orders.store') }}" class="form fv-plugins-bootstrap5 fv-plugins-framework" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="card-body border border-1 border-dark" data-select2-id="select2-data-125-ux9a">

                    @php
                        $default_items = [
                            [
                                'credit' => 20000,
                                'text' => '再一點點就夠了',
                                'color' => 'primary',
                            ],
                            [
                                'credit' => 40000,
                                'text' => '太好用了',
                                'color' => 'success',
                            ],
                            [
                                'credit' => 60000,
                                'text' => '我要成為作家了',
                                'color' => 'danger',
                            ],
                            [
                                'credit' => 100000,
                                'text' => '我已經是一位作家了',
                                'color' => 'info',
                            ],
                            [
                                'credit' => 200000,
                                'text' => '我是比較有名的作家了',
                                'color' => 'dark',
                            ],
                            [
                                'credit' => 400000,
                                'text' => '我想要花不完',
                                'color' => 'warning',
                            ],
                        ];
                    @endphp
                    <div class="row">
                        @foreach($default_items as $default_item)
                            <label class="col-12 col-md-6 radio-credit d-flex flex-stack mb-5 cursor-pointer" data-credit="{{ $default_item['credit'] }}">
                                <span class="d-flex align-items-center me-2">
                                    <span class="symbol symbol-50px me-6">
                                        <span class="symbol-label bg-light-{{ $default_item['color'] }} rounded-circle border border-3 border-{{ $default_item['color'] }}">
                                            <i class="fa-solid fa-gem fs-1 text-{{ $default_item['color'] }}"></i>
                                        </span>
                                    </span>

                                    <span class="d-flex flex-column">
                                        <span class="fw-bold fs-6">@lang('word.recharge') {{ $default_item['credit'] }} TOKEN</span>
                                        <span class="fs-7 text-muted">{{ $default_item['text'] }}</span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>


            

                    <input type="hidden" name="amount" value="400">
                    <input type="hidden" name="type" value="credit">


                    {{-- Separator --}}
                    <div class="separator separator-content my-10">
                        <span class="w-125px text-gray-900 fw-semibold fs-4">@lang('word.or')</span>
                    </div>


                    <div>
                        <div class="d-flex flex-column text-center">
                            <div class="row">
                                <div class="col-6 d-flex align-items-start justify-content-center mb-7">
                                    <span class="fw-bold fs-4 mt-1 me-2">TOKEN</span>
                                    <span class="fw-bold fs-3x" id="kt_modal_create_campaign_budget_label"></span>
                                    <span id="credit_value" class="fw-bold fs-3x">20000</span>
                                </div>
                                <div class="col-6 d-flex align-items-start justify-content-center mb-7">
                                    <span class="fw-bold fs-4 mt-1 me-2">NT$</span>
                                    <span class="fw-bold fs-3x" id="kt_modal_create_campaign_budget_label"></span>
                                    <span id="price_valuie" class="fw-bold fs-3x">400</span>
                                </div>

                            </div>

                            <div id="credit_slider" class="noUi-sm bg-danger"></div>
                        </div>
                    </div>

                    <div class="mt-10">
                        <button class="w-100 btn btn-dark fw-bold">@lang('word.confirm_order')</button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <div class="col-12 col-md-7">
        <div class="card card-flush h-xl-100">
            <div class="card-header pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800 mb-3">@lang('word.services_can_be_purchased_by_credit')</span>
                    <span class="text-gray-600 pt-1 fw-bold fs-6">@lang('word.fit_your_need')</span>
                </h3>

            </div>
            <div class="card-body py-3 mt-5">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fs-7 fw-bold border-0 text-gray-400">
                                <th class="min-w-200px" colspan="2">@lang('word.item_name')</th>
                                <th class="min-w-50px" colspan="2">@lang('word.value')</th>
                                <th class="min-w-100px pe-0" colspan="2">@lang('word.normal_price')</th>
                                <th class="min-w-100px" colspan="2">@lang('word.event_price')</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($extra_services as $extra_service)
                                <tr class="text-nowrap">
                                    <td colspan="2">
                                        <span class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">{{ $extra_service->name }}</span>
                                    </td>
                                    <td class="pe-0" colspan="2">
                                        <div class="d-flex">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $extra_service->value }}</span>
                                            {{-- <span class="text-danger min-w-50px d-block text-end fw-bold fs-6">-935</span> --}}
                                        </div>
                                    </td>
                                    <td class="pe-0" colspan="2">
                                        <div class="d-flex">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $extra_service->normal_price }} TOKEN</span>
                                            {{-- <span class="text-danger min-w-50px d-block text-end fw-bold fs-6">-935</span> --}}
                                        </div>
                                    </td>
                                    <td colspan="2">
                                        @if(isset($extra_service->sale_price))
                                        <div class="d-flex">
                                            <span class="text-dark fw-bold fs-6">{{ $extra_service->sale_price }} TOKEN</span>
                                            <span class="text-danger min-w-50px d-block text-end fw-bold fs-6">{{ (int)(($extra_service->sale_price - $extra_service->normal_price) / $extra_service->normal_price * 100) }}%</span>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach



                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('foot_js')
<script>
    var slider = document.querySelector("#credit_slider");
    var credit_value = $('#credit_value');
    var price_valuie = $('#price_valuie');
    var ratio = parseFloat("{{ gss('credit_ratio', 50) }}");

    noUiSlider.create(slider, {
        start: [20000],
        step: 500,
        connect: true,
        range: {
            "min": 5000,
            "max": 1000000
        }
    });

    slider.noUiSlider.on("update", function (values, handle) {

        //set value
        credit_value.text(Math.round(values[handle]));
        price_valuie.text(Math.round(values[handle] / ratio));
        
        //unselect radio
        // $(":checked").prop('checked', false);

        //update hidden
        $("[name='amount']").val(values[handle] / ratio);

        if (handle) {
            credit_value.innerHTML = Math.round(values[handle]);
        }
    });

    


    $(".radio-credit").on("click", function() {
        var credit = $(this).data('credit');
        var price = credit / ratio
        slider.noUiSlider.set(credit);
        $("[name='amount']").val(price);
        credit_value.val(credit);
        price_valuie.text(price);
    });
</script>
<style>
    .noUi-touch-area{
        border: 3px solid red;
        border-radius: 50%;
    }
</style>
@endpush