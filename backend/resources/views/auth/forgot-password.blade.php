@extends('layouts.app')
@section('title', 'Reset password · Northstar CRM')
@section('body')
<main class="form-shell"><section class="form-wrap"><form class="form-card" method="POST" action="{{ route('password.email') }}">@csrf
    <h1>Reset your password</h1><p class="muted">Enter your account email and we’ll send a reset link.</p>
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
    <button class="btn primary" type="submit">Send reset link</button><div class="form-links"><a href="{{ route('login') }}">Back to sign in</a></div>
</form></section></main>
@endsection
