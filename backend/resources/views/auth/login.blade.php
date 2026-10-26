@extends('layouts.app')
@section('title', 'Sign in · Northstar CRM')
@section('body')
<main class="form-shell"><section class="form-wrap">
    <div style="text-align:center;margin-bottom:20px;font-weight:750;font-size:19px;color:#182230"><span style="display:inline-grid;place-items:center;width:31px;height:31px;border-radius:10px;background:#6377ed;color:white;margin-right:8px">N</span>Northstar CRM</div>
    <form class="form-card" method="POST" action="{{ route('login.store') }}">@csrf
        <h1>Welcome back</h1><p class="muted">Sign in to continue to your workspace.</p>
        @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
        <label style="font-size:12px;color:#687489"><input type="checkbox" name="remember"> Keep me signed in</label>
        <button class="btn primary" type="submit">Sign in</button>
        <div class="form-links"><span></span><a href="{{ route('password.request') }}">Forgot password?</a></div>
    </form>
</section></main>
@endsection
