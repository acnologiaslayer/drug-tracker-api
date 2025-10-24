<?php

use App\Http\Middleware\AuthenticatedRateLimiter;
use App\Http\Middleware\PublicSearchRateLimiter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'throttle.public-search' => PublicSearchRateLimiter::class,
            'throttle.authenticated' => AuthenticatedRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
