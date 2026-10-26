<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
            if ($request->hasSession()) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $request->expectsJson() ? response()->json(['message' => 'Account is inactive.'], 403) : redirect()->route('login')->withErrors(['email' => 'This account is inactive.']);
        }

        return $next($request);
    }
}
