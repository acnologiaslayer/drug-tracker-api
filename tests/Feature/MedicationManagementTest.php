<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMedication;
use App\Services\RxNormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MedicationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_requires_authentication_to_manage_medications(): void
    {
        $this->ensureSqliteDriver();

        $response = $this->getJson('/api/medications');

        $response->assertUnauthorized();
    }

    public function test_it_can_add_medication(): void
    {
        $user = $this->authenticateUser();

        $this->mockRxNormService(function (MockInterface $mock): void {
            $mock->shouldReceive('validateRxcui')->with('198440')->andReturn(true);
            $mock->shouldReceive('getDrugDetails')->with('198440')->andReturn([
                'name' => 'Aspirin 81 MG',
                'base_names' => ['Aspirin'],
                'dose_forms' => ['Oral Tablet'],
            ]);
        });

        $response = $this->postJson('/api/medications', ['rxcui' => '198440']);

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Medication added successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'rxcui', 'drug_name', 'base_names', 'dose_form_group_names'],
            ]);

        $this->assertDatabaseHas('user_medications', [
            'user_id' => $user->id,
            'rxcui' => '198440',
        ]);
    }

    public function test_it_lists_user_medications(): void
    {
        $user = $this->authenticateUser();

        UserMedication::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $this->mockRxNormService(static function (MockInterface $mock): void {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->getJson('/api/medications');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_it_deletes_medication(): void
    {
        $user = $this->authenticateUser();

        $medication = UserMedication::factory()->create([
            'user_id' => $user->id,
            'rxcui' => '198440',
        ]);

        $this->mockRxNormService(static function (MockInterface $mock): void {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->deleteJson('/api/medications/198440');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Medication removed successfully.',
            ]);

        $this->assertDatabaseMissing('user_medications', [
            'id' => $medication->id,
        ]);
    }

    public function test_it_prevents_deleting_other_users_medications(): void
    {
        $user = $this->authenticateUser();

        $otherUser = User::factory()->create();
        UserMedication::factory()->create([
            'user_id' => $otherUser->id,
            'rxcui' => '198440',
        ]);

        $this->mockRxNormService(static function (MockInterface $mock): void {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->deleteJson('/api/medications/198440');

        $response->assertNotFound();

        $this->assertDatabaseHas('user_medications', [
            'user_id' => $otherUser->id,
            'rxcui' => '198440',
        ]);
    }

    public function test_it_handles_invalid_rxcui_when_adding_medication(): void
    {
        $this->authenticateUser();

        $this->mockRxNormService(function (MockInterface $mock): void {
            $mock->shouldReceive('validateRxcui')->with('invalid')->andReturn(false);
            $mock->shouldReceive('getDrugDetails')->never();
        });

        $response = $this->postJson('/api/medications', ['rxcui' => 'invalid']);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'The provided RXCUI is invalid.',
            ]);
    }

    public function test_it_handles_duplicate_medications(): void
    {
        $user = $this->authenticateUser();

        UserMedication::factory()->create([
            'user_id' => $user->id,
            'rxcui' => '198440',
        ]);

        $this->mockRxNormService(function (MockInterface $mock): void {
            $mock->shouldReceive('validateRxcui')->with('198440')->andReturn(true);
            $mock->shouldReceive('getDrugDetails')->never();
        });

        $response = $this->postJson('/api/medications', ['rxcui' => '198440']);

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Medication already exists for this user.',
            ]);
    }

    private function ensureSqliteDriver(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    private function authenticateUser(): User
    {
        $this->ensureSqliteDriver();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param callable(MockInterface):void $expectations
     */
    private function mockRxNormService(callable $expectations): void
    {
        $mock = Mockery::mock(RxNormService::class);
        $expectations($mock);

        $this->app->instance(RxNormService::class, $mock);
    }
}
