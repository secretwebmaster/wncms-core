@extends('frontend.theme.default.layouts.app')

@section('content')
    <div class="password-forgot">
        <h1>Forgot Password</h1>
        <p>Please enter your email address to receive a password reset link.</p>
        
        <form method="POST" action="{{ route('frontend.users.password.forgot.submit') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </div>
        </form>
    </div>
@endsection
