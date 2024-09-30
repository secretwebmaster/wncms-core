@extends('layouts.backend')

@section('content')

    @if(gss('use_custom_user_dashbaord'))
        @includeIf('backend.dashboards.custom_user_dashboard')
    @else
        <div class="row g-5 g-xl-10 mb-xl-10">
            @include('backend.parts.message')

            <div class="row g-5 gx-xl-10">
                {{-- Left --}}
                <div class="col-12">
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-transparent" data-theme="light" style="background-color: #1C325E;">
                                <div class="card-body d-flex ps-xl-15">
                                    <div class="m-0">
                                        <div class="position-relative fs-2x z-index-2 fw-bold text-white mb-7">
                                            <span class="me-2">@lang('word.welcome_back')!</span>
                                            <br>
                                            <span>@lang('word.pick_a_service_to_start')</span>
                                        </div>
                                    </div>
                                    <div class="ms-auto d-none d-md-block">
                                        <img class="mw-150px" src="{{ asset('wncms/images/logos/favicon.png') }}" class="position-absolute me-3 bottom-0 end-0 h-200px" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 gx-xl-10">

                {{-- Left --}}
                <div class="col-xl-4 mb-xl-10">
                    {{-- Plan --}}
                    <div class="card hover-elevate-up shadow-sm card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-5 mb-xl-10 h-xl-100" style="background-color: #F1416C;background-image:url('{{ asset('wncms/media/patterns/vector-1.png') }}')">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ auth()->user()->active_subscription->plan?->name ?? __('word.free_plan') }}</span>
                                <span class="text-white opacity-75 pt-1 fw-semibold fs-6">@lang('word.expiry_date') {{ auth()->user()->active_subscription?->expired_at }}</span>
                            </div>
                        </div>
                        <div class="card-body d-flex align-items-end pt-0">
                            <div class="d-flex align-items-center flex-column mt-3 w-100">
                                <div class="text-center">
                                    <img class="mw-50 mb-10" src="{{ asset('wncms/images/girl_3d.png') }}" alt="">
                                </div>
                                @if(auth()->user()->active_subscription?->plan?->monthly_word_limit > 0)
                                <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                    <span>@lang('word.used_words', ['count' => auth()->user()->words_month_count])</span>
                                    <span>{{ number_format( (float)( auth()->user()->words_month_count / auth()->user()->active_subscription?->plan?->monthly_word_limit) * 100, 2, '.', '') }}%</span>
                                </div>
                                <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                                    <div class="bg-white rounded h-8px" role="progressbar" style="width: {{ number_format( (float)( auth()->user()->words_month_count / auth()->user()->active_subscription?->plan?->monthly_word_limit) * 100, 2, '.', '') }}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right --}}
                <div class="col-xl-8 mb-5 mb-xl-10">
                    {{-- 上 --}}
                    <div class="row g-5 g-xl-10">

                        {{-- 上左 --}}
                        <div class="col-xl-6 mb-xl-10">
                            <div class="card hover-elevate-up shadow-sm card-flush h-md-50 mb-5 mb-xl-10 h-xl-100">
                                <div class="card-header pt-5">
                                    <div class="card-title d-flex flex-column">
                                        <div class="d-flex align-items-center">
                                            <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">@lang('word.posts')</span>
                                            <span class="badge badge-light-success fs-base">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-2 pb-4 d-flex align-items-center">
                                    <div class="d-flex flex-center me-10 pt-2">
                                        <i class="fa-solid fa-file-word fa-beat fs-3tx"></i>
                                    </div>
                                    <div class="d-flex flex-column content-justify-center w-100">
                                        <div class="d-flex fs-6 fw-semibold align-items-center">
                                            <div class="bullet w-8px h-6px rounded-2 bg-danger me-3"></div>
                                            <div class="text-gray-500 flex-grow-1 me-4">@lang('word.post_count')</div>
                                            <div class="fw-bolder text-gray-700 text-xxl-end">{{ auth()->user()->posts()->count() }}</div>
                                        </div>
                                        <div class="d-flex fs-6 fw-semibold align-items-center my-3">
                                            <div class="bullet w-8px h-6px rounded-2 bg-primary me-3"></div>
                                            <div class="text-gray-500 flex-grow-1 me-4">@lang('word.post_view_count')</div>
                                            <div class="fw-bolder text-gray-700 text-xxl-end">{{ auth()->user()->words_month_count }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- If credit model --}}
                        {{-- If vidoe model --}}

                    </div>

                </div>
            </div>
        </div>
    @endif
@endsection