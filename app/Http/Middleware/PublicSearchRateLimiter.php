<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class PublicSearchRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'public-search:' . $request->ip();
        $attempts = (int) config('rate_limit.public_search.attempts', 60);
        $seconds = (int) config('rate_limit.public_search.per_minutes', 1) * 60;

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

