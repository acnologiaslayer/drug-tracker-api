<?php

namespace App\Providers;

use App\Cache\RxNormCacheManager;
use App\Repositories\MedicationRepository;
use App\Services\DrugSearchService;
use App\Services\MedicationService;
use App\Services\RxNormService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClientInterface::class, static function (): ClientInterface {
            return new Client([
                'timeout' => (int) config('rxnorm.timeout', 10),
            ]);
        });

        $this->app->singleton(RxNormCacheManager::class, function ($app): RxNormCacheManager {
            return new RxNormCacheManager($app->make(CacheRepository::class));
        });

        $this->app->singleton(RxNormService::class, function ($app): RxNormService {
            return new RxNormService(
                $app->make(ClientInterface::class),
                $app->make(RxNormCacheManager::class),
                $app->make(LoggerInterface::class),
                $app->make(CacheRepository::class),
            );
        });

        $this->app->singleton(MedicationRepository::class);
        $this->app->singleton(DrugSearchService::class);
        $this->app->singleton(MedicationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }
}
