<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $identifier = $user?->id ?? $request->ip();
        $key = 'auth-rate:'.$identifier;
        $attempts = (int) config('rate_limit.authenticated.attempts', 120);
        $seconds = (int) config('rate_limit.authenticated.per_minutes', 1) * 60;

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, $seconds);

        return $next($request);
    }
}
