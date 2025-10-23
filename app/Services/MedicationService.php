<?php

namespace App\Services;

use App\Exceptions\DuplicateMedicationException;
use App\Exceptions\InvalidRxcuiException;
use App\Models\UserMedication;
use App\Repositories\MedicationRepository;
use Illuminate\Database\Eloquent\Collection;

class MedicationService
{
    public function __construct(
        private readonly RxNormService $rxNormService,
        private readonly MedicationRepository $repository,
    ) {
    }

    public function addMedication(int $userId, string $rxcui): UserMedication
    {
        if (! $this->rxNormService->validateRxcui($rxcui)) {
            throw new InvalidRxcuiException('The provided RXCUI is invalid.');
        }

        if ($this->repository->exists($userId, $rxcui)) {
            throw new DuplicateMedicationException('Medication already exists for this user.');
        }

        $details = $this->rxNormService->getDrugDetails($rxcui);

        return $this->repository->create([
            'user_id' => $userId,
            'rxcui' => $rxcui,
            'drug_name' => $details['name'] ?? '',
            'base_names' => $details['base_names'] ?? [],
            'dose_form_group_names' => $details['dose_forms'] ?? [],
        ]);
    }

    public function deleteMedication(int $userId, string $rxcui): bool
    {
        return $this->repository->deleteByRxcui($userId, $rxcui);
    }

    public function listMedications(int $userId): Collection
    {
        return $this->repository->getAllForUser($userId);
    }
}
