@extends('frontend.theme.default.layouts.app')

@section('content')
<div class="profile-container">
    <h2>@lang('wncms::word.my_account') <a class="small" href="{{ route('frontend.users.profile.edit') }}">[@lang('wncms::word.edit')]</a></h2>
    <table class="profile-table">
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
        </tr>
        @endforeach
    </table>
</div>
@endsection
