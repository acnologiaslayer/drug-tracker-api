<?php

namespace Tests\Unit\Services;

use App\Exceptions\DuplicateMedicationException;
use App\Exceptions\InvalidRxcuiException;
use App\Models\UserMedication;
use App\Repositories\MedicationRepository;
use App\Services\MedicationService;
use App\Services\RxNormService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MedicationServiceTest extends TestCase
{
    private MockInterface $rxNormService;

    private MockInterface $repository;

    private MedicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rxNormService = Mockery::mock(RxNormService::class);
        $this->repository = Mockery::mock(MedicationRepository::class);
        $this->service = new MedicationService($this->rxNormService, $this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_adds_medication_for_user(): void
    {
        $userId = 1;
        $rxcui = '198440';
        $details = [
            'name' => 'Aspirin 81 MG Oral Tablet',
            'base_names' => ['Aspirin'],
            'dose_forms' => ['Oral Tablet'],
        ];

        $this->rxNormService->shouldReceive('validateRxcui')
            ->once()
            ->with($rxcui)
            ->andReturn(true);

        $this->repository->shouldReceive('exists')
            ->once()
            ->with($userId, $rxcui)
            ->andReturn(false);

        $this->rxNormService->shouldReceive('getDrugDetails')
            ->once()
            ->with($rxcui)
            ->andReturn($details);

        $this->repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $payload) use ($userId, $rxcui, $details) {
                return $payload['user_id'] === $userId
                    && $payload['rxcui'] === $rxcui
                    && $payload['drug_name'] === $details['name']
                    && $payload['base_names'] === $details['base_names']
                    && $payload['dose_form_group_names'] === $details['dose_forms'];
            }))
            ->andReturn(new UserMedication([
                'user_id' => $userId,
                'rxcui' => $rxcui,
                'drug_name' => $details['name'],
                'base_names' => $details['base_names'],
                'dose_form_group_names' => $details['dose_forms'],
            ]));

        $medication = $this->service->addMedication($userId, $rxcui);

        $this->assertInstanceOf(UserMedication::class, $medication);
        $this->assertSame($userId, $medication->user_id);
        $this->assertSame($rxcui, $medication->rxcui);
    }

    public function test_it_prevents_duplicate_medications(): void
    {
        $userId = 1;
        $rxcui = '198440';

        $this->rxNormService->shouldReceive('validateRxcui')
            ->once()
            ->with($rxcui)
            ->andReturn(true);

        $this->repository->shouldReceive('exists')
            ->once()
            ->with($userId, $rxcui)
            ->andReturn(true);

        $this->expectException(DuplicateMedicationException::class);

        $this->service->addMedication($userId, $rxcui);
    }

    public function test_it_validates_rxcui_before_adding(): void
    {
        $this->rxNormService->shouldReceive('validateRxcui')
            ->once()
            ->with('invalid')
            ->andReturn(false);

        $this->expectException(InvalidRxcuiException::class);

        $this->service->addMedication(1, 'invalid');
    }

    public function test_it_deletes_medication_by_rxcui(): void
    {
        $userId = 1;
        $rxcui = '198440';

        $this->repository->shouldReceive('deleteByRxcui')
            ->once()
            ->with($userId, $rxcui)
            ->andReturn(true);

        $this->assertTrue($this->service->deleteMedication($userId, $rxcui));
    }

    public function test_it_lists_all_medications_for_user(): void
    {
        $userId = 1;
        $medications = new EloquentCollection([
            new UserMedication(['user_id' => $userId, 'rxcui' => '198440']),
        ]);

        $this->repository->shouldReceive('getAllForUser')
            ->once()
            ->with($userId)
            ->andReturn($medications);

        $this->assertSame($medications, $this->service->listMedications($userId));
    }
}
