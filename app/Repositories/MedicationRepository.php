<?php

namespace App\Repositories;

use App\Models\UserMedication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MedicationRepository
{
    public function exists(int $userId, string $rxcui): bool
    {
        return UserMedication::query()
            ->where('user_id', $userId)
            ->where('rxcui', $rxcui)
            ->exists();
    }

    public function create(array $attributes): UserMedication
    {
        return UserMedication::create($attributes);
    }

    public function deleteByRxcui(int $userId, string $rxcui): bool
    {
        return UserMedication::query()
            ->where('user_id', $userId)
            ->where('rxcui', $rxcui)
            ->delete() > 0;
    }

    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return UserMedication::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getAllForUser(int $userId): Collection
    {
        return UserMedication::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }
}
