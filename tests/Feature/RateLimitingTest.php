<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DrugSearchService;
use App\Services\MedicationService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $rateLimiterKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->rateLimiterKeys as $key) {
            RateLimiter::clear($key);
        }

        $this->rateLimiterKeys = [];
        Mockery::close();

        parent::tearDown();
    }

    public function test_public_drug_search_is_rate_limited(): void
    {
        $this->ensureSqliteDriver();

        Config::set('rate_limit.public_search.attempts', 1);
        Config::set('rate_limit.public_search.per_minutes', 1);

        $key = 'public-search:127.0.0.1';
        RateLimiter::clear($key);
        $this->rateLimiterKeys[] = $key;

        $this->mockDrugSearchService(function (MockInterface $mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->with('aspirin')
                ->andReturn([
                    [
                        'rxcui' => '198440',
                        'name' => 'Aspirin 81 MG Oral Tablet',
                        'ingredient_base_names' => ['Aspirin'],
                        'dosage_forms' => ['Oral Tablet'],
                    ],
                ]);
        });

        $this->getJson('/api/search/drugs?drug_name=aspirin')->assertOk();

        $response = $this->getJson('/api/search/drugs?drug_name=aspirin');

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ])
            ->assertJsonStructure(['retry_after']);
    }

    public function test_authenticated_routes_are_rate_limited(): void
    {
        $this->ensureSqliteDriver();

        Config::set('rate_limit.authenticated.attempts', 1);
        Config::set('rate_limit.authenticated.per_minutes', 1);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $key = 'auth-rate:' . $user->id;
        RateLimiter::clear($key);
        $this->rateLimiterKeys[] = $key;

        $this->mockMedicationService($user->id);

        $this->getJson('/api/medications')->assertOk();

        $response = $this->getJson('/api/medications');

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ])
            ->assertJsonStructure(['retry_after']);
    }

    private function ensureSqliteDriver(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    /**
     * @param callable(MockInterface): void $expectations
     */
    private function mockDrugSearchService(callable $expectations): void
    {
        $mock = Mockery::mock(DrugSearchService::class);
        $expectations($mock);

        $this->app->instance(DrugSearchService::class, $mock);
    }

    private function mockMedicationService(int $userId): void
    {
        $mock = Mockery::mock(MedicationService::class);
        $mock->shouldReceive('listMedications')
            ->once()
            ->with($userId)
            ->andReturn(new EloquentCollection());

        $this->app->instance(MedicationService::class, $mock);
    }
}
