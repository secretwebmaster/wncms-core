@extends('layouts.backend')

@section('content')

@include('backend.parts.message')

    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            {{-- Details --}}
            @include('backend.users.parts.info')

            {{-- Nav --}}
            @include('backend.users.parts.nav')
        </div>
    </div>

    {{-- Sign-in Method --}}
    <div class="card mb-5 mb-xl-10">

        {{-- Card header --}}
        <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_signin_method">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Sign-in Method</h3>
            </div>
        </div>

        {{-- Form --}}
        <form class="form fv-plugins-bootstrap5 fv-plugins-framework" method="POST">
            @csrf

            {{-- Content --}}
            <div id="kt_account_settings_signin_method" class="collapse show">

                
                {{-- Card body --}}
                <div class="card-body border-top p-3 p-md-9">
                    {{-- Email Address --}}
                    <div class="d-flex flex-wrap align-items-center">
                        {{-- Label --}}
                        <div id="kt_signin_email">
                            <div class="fs-6 fw-bold mb-1">Email Address</div>
                            <div class="fw-semibold text-gray-600">support@keenthemes.com</div>
                        </div>


                        {{-- Edit --}}
                        <div id="kt_signin_email_edit" class="flex-row-fluid d-none">
                            {{-- Form --}}
                            <form id="kt_signin_change_email" class="form fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate">
                                <div class="row mb-3">
                                    <div class="col-lg-6 mb-4 mb-lg-0">
                                        <div class="fv-row mb-0 fv-plugins-icon-container">
                                            <label for="emailaddress" class="form-label fs-6 fw-bold mb-3">Enter New Email Address</label>
                                            <input type="email" class="form-control form-control-sm" id="emailaddress" placeholder="Email Address" name="emailaddress" value="support@keenthemes.com">
                                        <div class="fv-plugins-message-container invalid-feedback"></div></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="fv-row mb-0 fv-plugins-icon-container">
                                            <label for="confirmemailpassword" class="form-label fs-6 fw-bold mb-3">Confirm Password</label>
                                            <input type="password" class="form-control form-control-sm" name="confirmemailpassword" id="confirmemailpassword">
                                        <div class="fv-plugins-message-container invalid-feedback"></div></div>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <button id="kt_signin_submit" type="button" class="btn btn-primary me-2 px-6">Update Email</button>
                                    <button id="kt_signin_cancel" type="button" class="btn btn-color-gray-400 btn-active-light-primary px-6">Cancel</button>
                                </div>
                            </form>


                        </div>


                        {{-- Action --}}
                        <div id="kt_signin_email_button" class="ms-auto">
                            <button class="btn btn-light btn-active-light-primary">Change Email</button>
                        </div>


                    </div>


                    {{-- Separator --}}
                    <div class="separator separator-dashed my-6"></div>


                    {{-- Password --}}
                    <div class="d-flex flex-wrap align-items-center mb-10">
                        {{-- Label --}}
                        <div id="kt_signin_password">
                            <div class="fs-6 fw-bold mb-1">Password</div>
                            <div class="fw-semibold text-gray-600">************</div>
                        </div>


                        {{-- Edit --}}
                        <div id="kt_signin_password_edit" class="flex-row-fluid d-none">
                            {{-- Form --}}
                            <form id="kt_signin_change_password" class="form fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate">
                                <div class="row mb-1">
                                    <div class="col-lg-3">
                                        <div class="fv-row mb-0 fv-plugins-icon-container">
                                            <label for="currentpassword" class="form-label fs-6 fw-bold mb-3">Current Password</label>
                                            <input type="password" class="form-control form-control-sm" name="currentpassword" id="currentpassword">
                                        <div class="fv-plugins-message-container invalid-feedback"></div></div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="fv-row mb-0 fv-plugins-icon-container">
                                            <label for="newpassword" class="form-label fs-6 fw-bold mb-3">New Password</label>
                                            <input type="password" class="form-control form-control-sm" name="newpassword" id="newpassword">
                                        <div class="fv-plugins-message-container invalid-feedback"></div></div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="fv-row mb-0 fv-plugins-icon-container">
                                            <label for="confirmpassword" class="form-label fs-6 fw-bold mb-3">Confirm New Password</label>
                                            <input type="password" class="form-control form-control-sm" name="confirmpassword" id="confirmpassword">
                                        <div class="fv-plugins-message-container invalid-feedback"></div></div>
                                    </div>
                                </div>
                                <div class="form-text mb-5">Password must be at least 8 character and contain symbols</div>
                                <div class="d-flex">
                                    <button id="kt_password_submit" type="button" class="btn btn-primary me-2 px-6">Update Password</button>
                                    <button id="kt_password_cancel" type="button" class="btn btn-color-gray-400 btn-active-light-primary px-6">Cancel</button>
                                </div>
                            </form>


                        </div>


                        {{-- Action --}}
                        <div id="kt_signin_password_button" class="ms-auto">
                            <button class="btn btn-light btn-active-light-primary">Reset Password</button>
                        </div>


                    </div>


                    {{-- Notice --}}
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                        {{-- Icon --}}
                        {{-- Svg Icon | path: icons/duotune/general/gen048.svg --}}
                        <span class="svg-icon svg-icon-2tx svg-icon-primary me-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M20.5543 4.37824L12.1798 2.02473C12.0626 1.99176 11.9376 1.99176 11.8203 2.02473L3.44572 4.37824C3.18118 4.45258 3 4.6807 3 4.93945V13.569C3 14.6914 3.48509 15.8404 4.4417 16.984C5.17231 17.8575 6.18314 18.7345 7.446 19.5909C9.56752 21.0295 11.6566 21.912 11.7445 21.9488C11.8258 21.9829 11.9129 22 12.0001 22C12.0872 22 12.1744 21.983 12.2557 21.9488C12.3435 21.912 14.4326 21.0295 16.5541 19.5909C17.8169 18.7345 18.8277 17.8575 19.5584 16.984C20.515 15.8404 21 14.6914 21 13.569V4.93945C21 4.6807 20.8189 4.45258 20.5543 4.37824Z" fill="currentColor"></path>
                                <path d="M10.5606 11.3042L9.57283 10.3018C9.28174 10.0065 8.80522 10.0065 8.51412 10.3018C8.22897 10.5912 8.22897 11.0559 8.51412 11.3452L10.4182 13.2773C10.8099 13.6747 11.451 13.6747 11.8427 13.2773L15.4859 9.58051C15.771 9.29117 15.771 8.82648 15.4859 8.53714C15.1948 8.24176 14.7183 8.24176 14.4272 8.53714L11.7002 11.3042C11.3869 11.6221 10.874 11.6221 10.5606 11.3042Z" fill="currentColor"></path>
                            </svg>
                        </span>


                        <!--end::Icon-->
                        {{-- Wrapper --}}
                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                            {{-- Content --}}
                            <div class="mb-3 mb-md-0 fw-semibold">
                                <h4 class="text-gray-900 fw-bold">Secure Your Account</h4>
                                <div class="fs-6 text-gray-700 pe-7">Two-factor authentication adds an extra layer of security to your account. To log in, in addition you'll need to provide a 6 digit code</div>
                            </div>


                            {{-- Action --}}
                            <a href="#" class="btn btn-primary px-6 align-self-center text-nowrap" data-bs-toggle="modal" data-bs-target="#kt_modal_two_factor_authentication">Enable</a>


                        </div>


                    </div>


                </div>


            </div>
            
        </form>

    </div>

@endsection
