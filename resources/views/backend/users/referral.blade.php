@extends('backend.users.account')

@section('account_content')

    {{-- invite_friedns_to_get_reward --}}
    <div class="card mb-5 mb-xl-10">

        {{-- Card header --}}
        <div class="card-header border-0 cursor-pointer px-3 px-md-9" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_signin_method">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">@lang('word.invite_friedns_to_get_reward')</h3>
            </div>
        </div>

        {{-- Content --}}
        <div id="kt_account_settings_signin_method" class="collapse show">
            {{-- Card body --}}
            <div class="card-body border-top p-3 p-md-9">

                {{-- Notice --}}
                <div class="mb-3">
                    <h4 class="text-gray-900 fw-bold">@lang('word.your_referral_link')</h4>
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                        {{-- Icon --}}
                        {{-- Svg Icon | path: icons/duotune/general/gen048.svg --}}
                        <span class="svg-icon svg-icon-2tx svg-icon-primary me-4 d-flex align-items-center">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M20.5543 4.37824L12.1798 2.02473C12.0626 1.99176 11.9376 1.99176 11.8203 2.02473L3.44572 4.37824C3.18118 4.45258 3 4.6807 3 4.93945V13.569C3 14.6914 3.48509 15.8404 4.4417 16.984C5.17231 17.8575 6.18314 18.7345 7.446 19.5909C9.56752 21.0295 11.6566 21.912 11.7445 21.9488C11.8258 21.9829 11.9129 22 12.0001 22C12.0872 22 12.1744 21.983 12.2557 21.9488C12.3435 21.912 14.4326 21.0295 16.5541 19.5909C17.8169 18.7345 18.8277 17.8575 19.5584 16.984C20.515 15.8404 21 14.6914 21 13.569V4.93945C21 4.6807 20.8189 4.45258 20.5543 4.37824Z" fill="currentColor"></path>
                                <path d="M10.5606 11.3042L9.57283 10.3018C9.28174 10.0065 8.80522 10.0065 8.51412 10.3018C8.22897 10.5912 8.22897 11.0559 8.51412 11.3452L10.4182 13.2773C10.8099 13.6747 11.451 13.6747 11.8427 13.2773L15.4859 9.58051C15.771 9.29117 15.771 8.82648 15.4859 8.53714C15.1948 8.24176 14.7183 8.24176 14.4272 8.53714L11.7002 11.3042C11.3869 11.6221 10.874 11.6221 10.5606 11.3042Z" fill="currentColor"></path>
                            </svg>
                        </span>

                        {{-- Content --}}
                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap align-items-center">
                            <div class="mb-3 mb-md-0 fw-semibold flex-grow-1">
                                <div class="fs-6 text-gray-700 pe-7 fw-bold"><input class="form-control w-100" type="text" value="{{ route('register')  . "?ref=" . auth()->id() }}"></div>
                            </div>

                            <button class="btn btn-primary px-6 align-self-center text-nowrap fw-bold" btn-copy-to-clipboard data-value="{{ route('register')  . "?ref=" . auth()->id() }}" data-original-text="@lang('word.click_to_copy')" data-copied-text="@lang('word.copied')">@lang('word.click_to_copy')</button>
                        </div>

                    </div>
                </div>

                <div class="border border-2 border-dark rounded">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap mb-0">
                            <thead class="table-dark">
                                <tr class="text-start fw-bold gs-0">

                                    <th>@lang('word.id')</th>
                                    <th>@lang('word.name')</th>

                                    @if(request()->show_detail)
                                    <th>@lang('word.updated_at')</th>
                                    @endif

                                    <th>@lang('word.created_at')</th>
                                </tr>
                            </thead>
                            <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                                @foreach([] as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>

                                    @if(request()->show_detail)
                                    <td>{{ $user->updated_at }}</td>
                                    @endif

                                    <td>{{ $user->created_at }}</td>
                                <tr>
                                    @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection