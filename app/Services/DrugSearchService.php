<?php

namespace App\Services;

class DrugSearchService
{
    public function __construct(private readonly RxNormService $rxNormService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $drugName): array
    {
        return $this->rxNormService->searchDrugs($drugName);
    }
}
