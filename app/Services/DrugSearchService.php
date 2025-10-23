<?php

namespace App\Services;

use App\Exceptions\RxNormApiException;

class DrugSearchService
{
    public function __construct(private readonly RxNormService $rxNormService)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $drugName): array
    {
        return $this->rxNormService->searchDrugs($drugName);
    }
}
