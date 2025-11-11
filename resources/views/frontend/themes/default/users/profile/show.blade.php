@extends('wncms::frontend.themes.default.layouts.app')

@section('content')
<div class="profile-container">
    <h2>@lang('wncms::word.my_account') <a class="small" href="{{ route('frontend.users.profile.edit') }}">[@lang('wncms::word.edit')]</a></h2>
    <table class="profile-table">
        <thead>
            <tr>
                <th>@lang('wncms::word.key')</th>
                <th>@lang('wncms::word.value')</th>
                <th>@lang('wncms::word.remark')</th>
            </tr>
        </thead>
        @foreach([
        'id',
        'first_name',
        'last_name',
        'nickname',
        'email',
        'username',
        'email_verified_at',
        'last_login_at',
        'created_at',
        'referrer_id',
        ] as $field)
        <tr>
            <th>{{ $field }}</th>
            <td>{{ $user->{$field} }}</td>
            <td></td>
        </tr>
        @endforeach

        @if(wncms()->hasPackage('wncms-ecommerce'))
        <tr>
            <th>plans</th>
            @if(!$user->hasPlan())
            <td>@lang('word.free_user')</td>
            @else
            <td>
                @foreach( $user->getPlans() as $userPlan)
                <span>{{ $userPlan->name }} (#{{ $userPlan->id }}) {{ $userPlan->activeSubscription?->expired_at }}</span>
                @endforeach
            </td>
            <td>
                <code>$user->getPlans()</code>
            </td>
            @endif
        </tr>
        @endif

    </table>

    {{-- User Credits --}}
    <h3>@lang('wncms::word.credits')</h3>
    @if($user->credits)
        @if($user->credits->isEmpty())
        <p>@lang('wncms::word.no_credits')</p>
        @else
        <table class="credits-table">
            <thead>
                <tr>
                    <th>@lang('wncms::word.credit_type')</th>
                    <th>@lang('wncms::word.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->credits as $credit)
                <tr>
                    <td>@lang('wncms::word.' . $credit->type)</td>
                    <td>{{ number_format($credit->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @endif

    @if(wncms()->hasPackage('wncms-ecommerce'))
    @if($user->hasPlan(16))
    <h3>This is vip only content</h3>
    @else
    <h3>This is free user content</h3>
    @endif
    @endif
</div>
@endsection