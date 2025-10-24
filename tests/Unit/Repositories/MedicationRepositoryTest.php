<?php

namespace Tests\Unit\Repositories;

use App\Models\UserMedication;
use App\Repositories\MedicationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MedicationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MedicationRepository;
    }

    public function test_it_checks_if_medication_exists_for_user(): void
    {
        $medication = UserMedication::factory()->create();

        $this->assertTrue($this->repository->exists($medication->user_id, $medication->rxcui));
        $this->assertFalse($this->repository->exists($medication->user_id, 'missing'));
    }

    public function test_it_creates_medication_record(): void
    {
        $medication = $this->repository->create([
            'user_id' => UserMedication::factory()->create()->user_id,
            'rxcui' => '198440',
            'drug_name' => 'Aspirin 81 MG Oral Tablet',
            'base_names' => ['Aspirin'],
            'dose_form_group_names' => ['Oral Tablet'],
        ]);

        $this->assertDatabaseHas('user_medications', [
            'id' => $medication->id,
            'rxcui' => '198440',
        ]);
    }

    public function test_it_deletes_medications_by_rxcui(): void
    {
        $medication = UserMedication::factory()->create(['rxcui' => '12345']);

        $deleted = $this->repository->deleteByRxcui($medication->user_id, '12345');

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('user_medications', ['id' => $medication->id]);
    }

    public function test_it_paginates_medications_for_user(): void
    {
        $userMedication = UserMedication::factory()->create();
        UserMedication::factory()->count(4)->create(['user_id' => $userMedication->user_id]);

        $paginator = $this->repository->paginateForUser($userMedication->user_id, 3);

        $this->assertSame(3, $paginator->perPage());
        $this->assertSame(5, $paginator->total());
    }

    public function test_it_gets_all_medications_for_user_ordered_by_recent(): void
    {
        $userMedication = UserMedication::factory()->create();
        $second = UserMedication::factory()->create([
            'user_id' => $userMedication->user_id,
            'created_at' => now()->addMinute(),
        ]);

        $medications = $this->repository->getAllForUser($userMedication->user_id);

        $this->assertCount(2, $medications);
        $this->assertTrue($medications->first()->is($second));
    }
}
