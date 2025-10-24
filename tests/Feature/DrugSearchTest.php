<?php

namespace Tests\Feature;

use App\Exceptions\RxNormApiException;
use App\Services\DrugSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DrugSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_can_search_drugs_without_authentication(): void
    {
        $this->mockDrugSearchService(function (MockInterface $mock): void {
            $mock->shouldReceive('search')
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

        $response = $this->getJson('/api/search/drugs?drug_name=aspirin');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['rxcui', 'name', 'ingredient_base_names', 'dosage_forms'],
                ],
            ]);
    }

    public function test_it_validates_drug_name_parameter(): void
    {
        $this->mockDrugSearchService(function (MockInterface $mock): void {
            $mock->shouldReceive('search')->never();
        });

        $response = $this->getJson('/api/search/drugs');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['drug_name']);
    }

    public function test_it_handles_rxnorm_api_errors(): void
    {
        $this->mockDrugSearchService(function (MockInterface $mock): void {
            $mock->shouldReceive('search')
                ->andThrow(new RxNormApiException('Service unavailable', 503));
        });

        $response = $this->getJson('/api/search/drugs?drug_name=aspirin');

        $response
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Drug search service is currently unavailable.',
            ]);
    }

    /**
     * @param  callable(MockInterface):void  $expectations
     */
    private function mockDrugSearchService(callable $expectations): void
    {
        $mock = Mockery::mock(DrugSearchService::class);
        $expectations($mock);

        $this->app->instance(DrugSearchService::class, $mock);
    }
}
