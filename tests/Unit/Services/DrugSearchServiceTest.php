<?php

namespace Tests\Unit\Services;

use App\Services\DrugSearchService;
use App\Services\RxNormService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DrugSearchServiceTest extends TestCase
{
    private MockInterface $rxNormService;

    private DrugSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rxNormService = Mockery::mock(RxNormService::class);
        $this->service = new DrugSearchService($this->rxNormService);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_delegates_search_to_rxnorm_service(): void
    {
        $expected = [
            [
                'rxcui' => '198440',
                'name' => 'Aspirin 81 MG Oral Tablet',
            ],
        ];

        $this->rxNormService->shouldReceive('searchDrugs')
            ->once()
            ->with('aspirin')
            ->andReturn($expected);

        $this->assertSame($expected, $this->service->search('aspirin'));
    }

    public function test_it_returns_empty_array_when_rxnorm_returns_no_results(): void
    {
        $this->rxNormService->shouldReceive('searchDrugs')
            ->once()
            ->with('unknown')
            ->andReturn([]);

        $this->assertSame([], $this->service->search('unknown'));
    }
}
