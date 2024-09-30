@extends('layouts.install')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">

            <div class="card">
                <div class="card-header align-items-center">
                    <h2>@lang('word.install')</h2>
                </div>

                <div class="card-body">
                    <div class="stepper stepper-pills stepper-column d-flex flex-column flex-lg-row" id="kt_stepper_example_vertical">

                        {{-- Tabs --}}
                        <div class="d-flex flex-row-auto me-10">
                            <div class="stepper-nav flex-cente">
                                {{-- Step 1 --}}
                                <div class="stepper-item me-5 current" data-kt-stepper-element="nav" data-kt-stepper-action="step">
                                    <div class="stepper-wrapper d-flex align-items-center">
                                        <div class="stepper-icon w-40px h-40px">
                                            <i class="stepper-check fas fa-check"></i>
                                            <span class="stepper-number">1</span>
                                        </div>
                                        <div class="stepper-label">
                                            <h3 class="stepper-title">@lang('word.step_no',['step'=>1])</h3>
                                            <div class="stepper-desc">@lang('word.system_check')</div>
                                        </div>
                                    </div>
                                    <div class="stepper-line h-40px"></div>
                                </div>
    
                                {{-- Step2 --}}
                                <div class="stepper-item me-5 " data-kt-stepper-element="nav" data-kt-stepper-action="step">
                                    <div class="stepper-wrapper d-flex align-items-center">
                                        <div class="stepper-icon w-40px h-40px">
                                            <i class="stepper-check fas fa-check"></i>
                                            <span class="stepper-number">2</span>
                                        </div>
                                        <div class="stepper-label">
                                            <h3 class="stepper-title">@lang('word.step_no',['step'=>2])</h3>
                                            <div class="stepper-desc">@lang('word.website_info')</div>
                                        </div>
                                    </div>
                                    <div class="stepper-line h-40px"></div>
                                </div>
    
                                {{-- Step3 --}}
                                <div class="stepper-item me-5" data-kt-stepper-element="nav" data-kt-stepper-action="step">
                                    <div class="stepper-wrapper d-flex align-items-center">
                                        <div class="stepper-icon w-40px h-40px">
                                            <i class="stepper-check fas fa-check"></i>
                                            <span class="stepper-number">3</span>
                                        </div>
                                        <div class="stepper-label">
                                            <h3 class="stepper-title">@lang('word.step_no',['step'=>3])</h3>
                                            <div class="stepper-desc">@lang('word.database_info')</div>
                                        </div>
                                    </div>
                                    <div class="stepper-line h-40px"></div>
                                </div>
    
                                {{-- Step4 --}}
                                <div class="stepper-item me-5" data-kt-stepper-element="nav" data-kt-stepper-action="step">
                                    <div class="stepper-wrapper d-flex align-items-center">
                                        <div class="stepper-icon w-40px h-40px">
                                            <i class="stepper-check fas fa-check"></i>
                                            <span class="stepper-number">4</span>
                                        </div>
                                        <div class="stepper-label">
                                            <h3 class="stepper-title">@lang('word.step_no',['step'=>4])</h3>
                                            <div class="stepper-desc">@lang('word.admin_info')</div>
                                        </div>
                                    </div>
                                    <div class="stepper-line h-40px"></div>
                                </div>
                            </div>
                        </div>
                       
                        {{-- Content --}}
                        <div class="flex-row-fluid">
                            <form action="{{ route('install.submit') }}" method="POST" id="install_form">
                                @csrf
                                <div class="mb-5">
                                    {{-- Step 1 --}}
                                    <div class="flex-column current" data-kt-stepper-element="content">
                                        <h2 class="mb-3 bg-dark text-white rounded p-2">@lang('word.php_version')</h2>
                                        <div class="d-flex w-100 align-items-center mb-20">
                                            <span class="fw-bold fs-4">PHP 8.1</span>                                                
                                            @if(!empty($checks['php']))
                                            <i class="fa fa-check text-success ms-auto"></i>
                                            @else
                                            <i class="fa fa-cross  text-danger ms-auto"></i><span>@lang('word.current_version'): {{ PHP_VERSION }}</span>
                                            @endif
                                        </div>           

                                        <h2 class="mb-3 bg-dark text-white rounded p-2">@lang('word.php_extension')</h2>
                                        @foreach ($required_extensions as $required_extension)
                                            <div class="d-flex w-100 align-items-center">
                                                <span class="fw-bold fs-4">{{ $required_extension }}</span>                                                
                                                @if(!empty($checks[$required_extension]))
                                                <i class="fa fa-check text-success ms-auto"></i>
                                                @else
                                                <i class="fa fa-cross  text-danger ms-auto"></i>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Step 2 --}}
                                    <div class="flex-column" data-kt-stepper-element="content">
                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.site_name')</label>
                                            <input type="text" class="form-control form-control-solid" name="site_name" placeholder="" value="{{ old('site_name') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.site_url')</label>
                                            <input type="text" class="form-control form-control-solid" name="site_url" placeholder="" value="{{ old('site_url') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.license')</label>
                                            <input type="text" class="form-control form-control-solid" name="license" placeholder="" value="{{ old('license') }}" required/>
                                        </div>
                                    </div>

                                    {{-- Step 3 --}}
                                    <div class="flex-column" data-kt-stepper-element="content">
                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.database_host')</label>
                                            <input type="text" class="form-control form-control-solid" name="database_host" placeholder="" value="{{ old('database_host','localhost') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.database_port')</label>
                                            <input type="text" class="form-control form-control-solid" name="database_port" placeholder="" value="{{ old('database_port', 3306) }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.database_name')</label>
                                            <input type="text" class="form-control form-control-solid" name="database_name" placeholder="" value="{{ old('database_name') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.database_user')</label>
                                            <input type="text" class="form-control form-control-solid" name="database_user" placeholder="" value="{{ old('database_user') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.database_password')</label>
                                            <input type="text" class="form-control form-control-solid" name="database_password" placeholder="" value="{{ old('database_password') }}" required/>
                                        </div>
                                    </div>

                                    {{-- Step 4 --}}
                                    <div class="flex-column" data-kt-stepper-element="content">
                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.admin_username')</label>
                                            <input type="text" class="form-control form-control-solid" name="admin_username" placeholder="" value="{{ old('admin_username') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.admin_email')</label>
                                            <input type="text" class="form-control form-control-solid" name="admin_email" placeholder="" value="{{ old('admin_email') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.password')</label>
                                            <input type="password" class="form-control form-control-solid" name="password" placeholder="" value="{{ old('password') }}" required/>
                                        </div>

                                        <div class="fv-row mb-10">
                                            <label class="form-label">@lang('word.password_confirmation')</label>
                                            <input type="password" class="form-control form-control-solid" name="password_confirmation" placeholder="" value="{{ old('password_confirmation') }}" required/>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="d-flex flex-stack">
                                    <div class="me-2">
                                        <button type="button" class="btn btn-light btn-active-light-primary" data-kt-stepper-action="previous">
                                            @lang('word.back')
                                        </button>
                                    </div>

                                    <div>
                                        <button type="submit" class="btn btn-primary" data-kt-stepper-action="submit">
                                            <span class="indicator-label">
                                                @lang('word.submit')
                                            </span>
                                            <span class="indicator-progress">
                                                @lang('word.please_wait')... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                            </span>
                                        </button>

                                        <button type="button" class="btn btn-primary fw-bold" data-kt-stepper-action="next">@lang('word.next_step')</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

           
        </div>
    </div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', (event) => {

        jQuery.extend(jQuery.validator.messages, {
            required: "{{ __('word.this_field_is_required') }}",
            remote: "Por favor, rellena este campo.",
            email: "Por favor, escribe una dirección de correo válida",
            url: "Por favor, escribe una URL válida.",
            date: "Por favor, escribe una fecha válida.",
            dateISO: "Por favor, escribe una fecha (ISO) válida.",
            number: "Por favor, escribe un número entero válido.",
            digits: "Por favor, escribe sólo dígitos.",
            creditcard: "Por favor, escribe un número de tarjeta válido.",
            equalTo: "Por favor, escribe el mismo valor de nuevo.",
            accept: "Por favor, escribe un valor con una extensión aceptada.",
            maxlength: jQuery.validator.format("Por favor, no escribas más de {0} caracteres."),
            minlength: jQuery.validator.format("Por favor, no escribas menos de {0} caracteres."),
            rangelength: jQuery.validator.format("Por favor, escribe un valor entre {0} y {1} caracteres."),
            range: jQuery.validator.format("Por favor, escribe un valor entre {0} y {1}."),
            max: jQuery.validator.format("Por favor, escribe un valor menor o igual a {0}."),
            min: jQuery.validator.format("Por favor, escribe un valor mayor o igual a {0}.")
        });

        // Stepper lement
        var element = document.querySelector("#kt_stepper_example_vertical");

        // Initialize Stepper
        var stepper = new KTStepper(element);

        // Handle navigation click
        stepper.on("kt.stepper.click", function (stepper) {
            stepper.goTo(stepper.getClickedStepIndex()); // go to clicked step
        });
        // Handle next step
        stepper.on("kt.stepper.next", function (stepper) {
            if($('#install_form').valid()){
                stepper.goNext(); // go next step
            }
        });

        // Handle previous step
        stepper.on("kt.stepper.previous", function (stepper) {
            stepper.goPrevious(); // go previous step
        });
     
    });
</script>

@endsection
@endsection