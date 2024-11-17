@extends('frontend.theme.default.layouts.app')

@section('content')
<div class="profile-container">
    <h2>@lang('wncms::word.edit_profile')</h2>
    <form method="POST" action="{{ route('frontend.users.profile.update') }}">
        @csrf
        <table class="profile-table">
            @foreach([
                'first_name',
                'last_name',
                'nickname',
                'email',
                'username',
            ] as $field)
            <tr>
                <th>{{ $field }}</th>
                <td>
                    <input 
                        type="text" 
                        name="{{ $field }}" 
                        class="form-control" 
                        value="{{ old($field, $user->{$field}) }}" 
                        @if($field === 'email') type="email" @endif
                        @if($field === 'username') readonly @endif
                    >
                </td>
            </tr>
            @endforeach
        </table>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">@lang('wncms::word.save_changes')</button>
            <a href="{{ route('frontend.users.profile') }}" class="btn btn-secondary">@lang('wncms::word.cancel')</a>
        </div>
    </form>
</div>
@endsection
