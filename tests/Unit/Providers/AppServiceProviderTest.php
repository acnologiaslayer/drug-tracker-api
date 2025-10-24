<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_it_registers_service_singletons(): void
    {
        $app = app();
        $provider = new AppServiceProvider($app);
        $provider->register();

        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $app->make(\GuzzleHttp\ClientInterface::class));
        $this->assertInstanceOf(\App\Cache\RxNormCacheManager::class, $app->make(\App\Cache\RxNormCacheManager::class));
        $this->assertInstanceOf(\App\Services\RxNormService::class, $app->make(\App\Services\RxNormService::class));
        $this->assertInstanceOf(\App\Repositories\MedicationRepository::class, $app->make(\App\Repositories\MedicationRepository::class));
        $this->assertInstanceOf(\App\Services\DrugSearchService::class, $app->make(\App\Services\DrugSearchService::class));
        $this->assertInstanceOf(\App\Services\MedicationService::class, $app->make(\App\Services\MedicationService::class));

        $cacheManagerA = $app->make(\App\Cache\RxNormCacheManager::class);
        $cacheManagerB = $app->make(\App\Cache\RxNormCacheManager::class);
        $this->assertSame($cacheManagerA, $cacheManagerB);
    }

    public function test_it_registers_api_routes_during_boot(): void
    {
        $originalRoute = Route::getFacadeRoot();
        $routeMock = Mockery::mock();
        $routeMock->shouldReceive('prefix')->once()->with('api')->andReturnSelf();
        $routeMock->shouldReceive('middleware')->once()->with('api')->andReturnSelf();
        $routeMock->shouldReceive('group')->once()->with(base_path('routes/api.php'));

        Route::swap($routeMock);

        try {
            $provider = new AppServiceProvider(app());
            $provider->boot();
        } finally {
            Route::swap($originalRoute);
        }
    }
}
