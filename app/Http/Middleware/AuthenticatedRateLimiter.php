<?php<?php



namespace App\Http\Middleware;namespace App\Http\Middleware;



use Closure;use Closure;

use Illuminate\Http\Request;use Illuminate\Http\Request;

use Illuminate\Support\Facades\RateLimiter;use Illuminate\Support\Facades\RateLimiter;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class AuthenticatedRateLimiterclass AuthenticatedRateLimiter

{{

    public function handle(Request $request, Closure $next)    public function handle(Request $request, Closure $next)

    {    {

        $user = $request->user();        $user = $request->user();

        $identifier = $user?->id ?? $request->ip();        $identifier = $user?->id ?? $request->ip();

        $key = 'auth-rate:' . $identifier;        $key = 'auth-rate:' . $identifier;

        $attempts = (int) config('rate_limit.authenticated.attempts', 120);        $attempts = (int) config('rate_limit.authenticated.attempts', 120);

        $seconds = (int) config('rate_limit.authenticated.per_minutes', 1) * 60;        $seconds = (int) config('rate_limit.authenticated.per_minutes', 1) * 60;



        if (RateLimiter::tooManyAttempts($key, $attempts)) {        if (RateLimiter::tooManyAttempts($key, $attempts)) {

            $retryAfter = RateLimiter::availableIn($key);            $retryAfter = RateLimiter::availableIn($key);



            return response()->json([            return response()->json([

                'success' => false,                'success' => false,

                'message' => 'Too many requests. Please try again later.',                'message' => 'Too many requests. Please try again later.',

                'retry_after' => $retryAfter,                'retry_after' => $retryAfter,

            ], Response::HTTP_TOO_MANY_REQUESTS);            ], Response::HTTP_TOO_MANY_REQUESTS);

        }        }



        RateLimiter::hit($key, $seconds);        RateLimiter::hit($key, $seconds);



        return $next($request);        return $next($request);

    }    }

}}

