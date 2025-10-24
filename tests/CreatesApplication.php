<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $router = $app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('throttle.public-search', \App\Http\Middleware\PublicSearchRateLimiter::class);
        $router->aliasMiddleware('throttle.authenticated', \App\Http\Middleware\AuthenticatedRateLimiter::class);

        return $app;
    }
}
