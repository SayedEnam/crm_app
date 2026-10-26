@extends('layouts.app')
@section('title', 'Choose a new password · Northstar CRM')
@section('body')
<main class="form-shell"><section class="form-wrap"><form class="form-card" method="POST" action="{{ route('password.update') }}">@csrf
    <h1>Choose a new password</h1><p class="muted">Use at least 12 characters.</p>
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="email">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" required autocomplete="new-password" minlength="12">@error('password')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
    <button class="btn primary" type="submit">Save new password</button>
</form></section></main>
@endsection
