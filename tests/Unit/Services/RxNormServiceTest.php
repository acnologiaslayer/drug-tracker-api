<?php

namespace Tests\Unit\Services;

use App\Cache\RxNormCacheManager;
use App\Exceptions\RxNormApiException;
use App\Services\RxNormService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class RxNormServiceTest extends TestCase
{
    private MockInterface $httpClient;

    private MockInterface $cacheManager;

    private MockInterface $logger;

    private RxNormService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(ClientInterface::class);
        $this->cacheManager = Mockery::mock(RxNormCacheManager::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        Config::set('rxnorm.base_url', 'https://rxnav.test');
        Config::set('rxnorm.timeout', 5);

        $this->service = new RxNormService(
            $this->httpClient,
            $this->cacheManager,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_searches_drugs_by_name(): void
    {
        $drugResponse = [
            'drugGroup' => [
                'conceptGroup' => [
                    [
                        'tty' => 'SBD',
                        'conceptProperties' => [
                            ['rxcui' => '198440', 'name' => 'Aspirin 81 MG Oral Tablet'],
                            ['rxcui' => '198441', 'name' => 'Aspirin 325 MG Oral Tablet'],
                        ],
                    ],
                ],
            ],
        ];

        $this->logger->shouldReceive('error')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::on(function (array $options) {
                return ($options['query']['name'] ?? null) === 'aspirin'
                    && ($options['query']['tty'] ?? null) === 'SBD'
                    && ($options['timeout'] ?? null) === 5;
            }))
            ->andReturn(new Response(200, [], json_encode($drugResponse)));

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->cacheManager->shouldReceive('rememberDrugDetails')
            ->twice()
            ->andReturn([
                'base_names' => ['Aspirin'],
                'dose_forms' => ['Oral Tablet'],
            ]);

        $results = $this->service->searchDrugs('aspirin');

        $this->assertCount(2, $results);
        $this->assertSame('198440', $results[0]['rxcui']);
        $this->assertSame(['Aspirin'], $results[0]['ingredient_base_names']);
        $this->assertSame(['Oral Tablet'], $results[0]['dosage_forms']);
    }

    public function test_it_returns_empty_results_for_blank_search_terms(): void
    {
        $this->cacheManager->shouldReceive('rememberSearch')->never();
        $this->httpClient->shouldReceive('request')->never();

        $results = $this->service->searchDrugs('   ');

        $this->assertSame([], $results);
    }

    public function test_it_fetches_drug_details_from_rxnorm(): void
    {
        $historyResponse = [
            'rxcuiHistoryStatus' => [
                'derivedConcepts' => [
                    'ingredientAndStrength' => [
                        ['baseName' => 'Aspirin'],
                        ['baseName' => 'Aspirin'],
                    ],
                    'doseFormGroupConcept' => [
                        ['doseFormGroupName' => 'Oral Tablet'],
                        ['doseFormGroupName' => 'Delayed Release Tablet'],
                    ],
                ],
            ],
        ];

        $validateResponse = [
            'idGroup' => [
                'name' => 'Aspirin 81 MG Oral Tablet',
            ],
        ];

        $this->logger->shouldReceive('error')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/198440/historystatus.json', Mockery::on(fn (array $options) => ($options['timeout'] ?? null) === 5))
            ->andReturn(new Response(200, [], json_encode($historyResponse)));

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/198440.json', Mockery::on(fn (array $options) => ($options['timeout'] ?? null) === 5))
            ->andReturn(new Response(200, [], json_encode($validateResponse)));

        $this->cacheManager->shouldReceive('rememberDrugDetails')
            ->once()
            ->with('198440', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $details = $this->service->getDrugDetails('198440');

        $this->assertSame('Aspirin 81 MG Oral Tablet', $details['name']);
        $this->assertSame(['Aspirin'], $details['base_names']);
        $this->assertSame(['Oral Tablet', 'Delayed Release Tablet'], $details['dose_forms']);
    }

    public function test_it_validates_rxcui_values(): void
    {
        $responseBody = [
            'idGroup' => [
                'rxnormId' => ['198440', '123'],
            ],
        ];

        $this->logger->shouldReceive('error')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/198440.json', Mockery::on(fn (array $options) => ($options['timeout'] ?? null) === 5))
            ->andReturn(new Response(200, [], json_encode($responseBody)));

        $isValid = $this->service->validateRxcui('198440');

        $this->assertTrue($isValid);
    }

    public function test_it_returns_false_for_invalid_rxcui_responses(): void
    {
        $responseBody = [
            'idGroup' => [],
        ];

        $this->logger->shouldReceive('error')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/invalid.json', Mockery::on(fn (array $options) => ($options['timeout'] ?? null) === 5))
            ->andReturn(new Response(200, [], json_encode($responseBody)));

        $this->assertFalse($this->service->validateRxcui('invalid'));
    }

    public function test_it_returns_false_when_api_reports_missing_rxcui(): void
    {
        $this->logger->shouldReceive('error')->once();

        $exception = new class ('Not Found', 404) extends \Exception implements GuzzleException {
        };

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/000.json', Mockery::type('array'))
            ->andThrow($exception);

        $this->assertFalse($this->service->validateRxcui('000'));
    }

    public function test_it_wraps_api_errors_in_custom_exception(): void
    {
        $this->logger->shouldReceive('error')->once();

        $exception = new class ('Timeout', 500) extends \Exception implements GuzzleException {
        };

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::type('array'))
            ->andThrow($exception);

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->expectException(RxNormApiException::class);
        $this->expectExceptionMessage('Failed to communicate with RxNorm API.');

        $this->service->searchDrugs('aspirin');
    }
}
