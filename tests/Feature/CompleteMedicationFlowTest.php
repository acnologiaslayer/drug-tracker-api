<?php

namespace Tests\Feature;

use App\Services\RxNormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CompleteMedicationFlowTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $rxNormService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rxNormService = Mockery::mock(RxNormService::class);
        app()->instance(RxNormService::class, $this->rxNormService);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_user_can_complete_medication_management_flow(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $registerResponse->assertCreated();

        $token = $registerResponse->json('data.access_token');
        $this->assertNotEmpty($token);

        $this->rxNormService->shouldReceive('searchDrugs')
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

        $searchResponse = $this->getJson('/api/search/drugs?drug_name=aspirin');

        $searchResponse->assertOk()
            ->assertJsonPath('data.0.rxcui', '198440');

        $this->rxNormService->shouldReceive('validateRxcui')
            ->once()
            ->with('198440')
            ->andReturn(true);

        $this->rxNormService->shouldReceive('getDrugDetails')
            ->once()
            ->with('198440')
            ->andReturn([
                'name' => 'Aspirin 81 MG Oral Tablet',
                'base_names' => ['Aspirin'],
                'dose_forms' => ['Oral Tablet'],
            ]);

        $addResponse = $this->withToken($token)
            ->postJson('/api/medications', ['rxcui' => '198440']);

        $addResponse->assertCreated()
            ->assertJsonPath('data.rxcui', '198440');

        $this->assertDatabaseHas('user_medications', [
            'rxcui' => '198440',
        ]);

        $listResponse = $this->withToken($token)->getJson('/api/medications');

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rxcui', '198440');

        $deleteResponse = $this->withToken($token)
            ->deleteJson('/api/medications/198440');

        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('user_medications', [
            'rxcui' => '198440',
        ]);

        $this->withToken($token)
            ->getJson('/api/medications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
