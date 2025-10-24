<?php

namespace Tests\Unit\Services;

use App\Cache\RxNormCacheManager;
use App\Exceptions\RxNormApiException;
use App\Services\RxNormService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepositoryContract;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RxNormServiceTest extends TestCase
{
    private MockInterface $httpClient;

    private MockInterface $cacheManager;

    private MockInterface $logger;

    private CacheRepositoryContract $cacheStore;

    private RxNormService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(ClientInterface::class);
        $this->cacheManager = Mockery::mock(RxNormCacheManager::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->cacheStore = new CacheRepository(new ArrayStore);

        Config::set('rxnorm.base_url', 'https://rxnav.test');
        Config::set('rxnorm.timeout', 5);
        Config::set('rxnorm.retry', [
            'attempts' => 3,
            'delay_ms' => 0,
            'backoff_multiplier' => 1,
        ]);
        Config::set('rxnorm.circuit_breaker', [
            'failure_threshold' => 5,
            'cooldown_seconds' => 60,
        ]);

        $this->service = new RxNormService(
            $this->httpClient,
            $this->cacheManager,
            $this->logger,
            $this->cacheStore,
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
        $this->logger->shouldReceive('warning')->never();

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

    public function test_search_results_are_limited_to_five_entries(): void
    {
        $conceptProperties = [];

        for ($i = 1; $i <= 7; $i++) {
            $conceptProperties[] = [
                'rxcui' => 'RX'.$i,
                'name' => 'Drug '.$i,
            ];
        }

        $drugResponse = [
            'drugGroup' => [
                'conceptGroup' => [
                    [
                        'tty' => 'SBD',
                        'conceptProperties' => $conceptProperties,
                    ],
                ],
            ],
        ];

        $this->logger->shouldReceive('error')->never();
        $this->logger->shouldReceive('warning')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode($drugResponse)));

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->cacheManager->shouldReceive('rememberDrugDetails')
            ->times(5)
            ->andReturn([
                'base_names' => ['Example'],
                'dose_forms' => ['Tablet'],
            ]);

        $results = $this->service->searchDrugs('aspirin');

        $this->assertCount(5, $results);
    }

    public function test_it_returns_cached_search_results_without_http_requests(): void
    {
        $cachedResults = [
            [
                'rxcui' => '198440',
                'name' => 'Aspirin 81 MG Oral Tablet',
                'ingredient_base_names' => ['Aspirin'],
                'dosage_forms' => ['Oral Tablet'],
            ],
        ];

        $this->logger->shouldReceive('error')->never();
        $this->logger->shouldReceive('warning')->never();

        $this->httpClient->shouldReceive('request')->never();
        $this->cacheManager->shouldReceive('rememberDrugDetails')->never();

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturn($cachedResults);

        $results = $this->service->searchDrugs('aspirin');

        $this->assertSame($cachedResults, $results);
    }

    public function test_it_returns_empty_results_for_blank_search_terms(): void
    {
        $this->logger->shouldReceive('error')->never();
        $this->logger->shouldReceive('warning')->never();

        $this->cacheManager->shouldReceive('rememberSearch')->never();
        $this->httpClient->shouldReceive('request')->never();

        $results = $this->service->searchDrugs('   ');

        $this->assertSame([], $results);
    }

    public function test_it_returns_cached_drug_details_without_http_requests(): void
    {
        $cachedDetails = [
            'name' => 'Aspirin 81 MG Oral Tablet',
            'base_names' => ['Aspirin'],
            'dose_forms' => ['Oral Tablet'],
        ];

        $this->logger->shouldReceive('error')->never();
        $this->logger->shouldReceive('warning')->never();

        $this->httpClient->shouldReceive('request')->never();

        $this->cacheManager->shouldReceive('rememberDrugDetails')
            ->once()
            ->with('198440', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturn($cachedDetails);

        $details = $this->service->getDrugDetails('198440');

        $this->assertSame($cachedDetails, $details);
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
        $this->logger->shouldReceive('warning')->never();

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
        $this->logger->shouldReceive('warning')->never();

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
        $this->logger->shouldReceive('warning')->never();

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/invalid.json', Mockery::on(fn (array $options) => ($options['timeout'] ?? null) === 5))
            ->andReturn(new Response(200, [], json_encode($responseBody)));

        $this->assertFalse($this->service->validateRxcui('invalid'));
    }

    public function test_it_returns_false_when_api_reports_missing_rxcui(): void
    {
        $this->logger->shouldReceive('error')->once();
        $this->logger->shouldReceive('warning')->never();

        $exception = new class('Not Found', 404) extends \Exception implements GuzzleException {};

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/rxcui/000.json', Mockery::type('array'))
            ->andThrow($exception);

        $this->assertFalse($this->service->validateRxcui('000'));
    }

    public function test_it_wraps_api_errors_in_custom_exception(): void
    {
        Config::set('rxnorm.retry.attempts', 1);

        $this->logger->shouldReceive('error')->once();
        $this->logger->shouldReceive('warning')->never();

        $exception = new class('Timeout', 500) extends \Exception implements GuzzleException {};

        $this->httpClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::type('array'))
            ->andThrow($exception);

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->cacheManager->shouldReceive('rememberDrugDetails')->never();

        $this->expectException(RxNormApiException::class);
        $this->expectExceptionMessage('Failed to communicate with RxNorm API.');

        $this->service->searchDrugs('aspirin');
    }

    public function test_it_retries_failed_requests_before_throwing_exception(): void
    {
        Config::set('rxnorm.retry.attempts', 3);
        Config::set('rxnorm.circuit_breaker.failure_threshold', 10);

        $this->logger->shouldReceive('error')->times(3);
        $this->logger->shouldReceive('warning')->never();

        $this->httpClient->shouldReceive('request')
            ->times(3)
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::type('array'))
            ->andThrow(
                new class('Server Error', 500) extends \Exception implements GuzzleException {},
                new class('Server Error', 500) extends \Exception implements GuzzleException {},
                new class('Server Error', 500) extends \Exception implements GuzzleException {},
            );

        $this->cacheManager->shouldReceive('rememberSearch')
            ->once()
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->cacheManager->shouldReceive('rememberDrugDetails')->never();

        $this->expectException(RxNormApiException::class);
        $this->expectExceptionMessage('Failed to communicate with RxNorm API.');

        $this->service->searchDrugs('aspirin');
    }

    public function test_it_opens_circuit_after_consecutive_failures(): void
    {
        Config::set('rxnorm.retry.attempts', 1);
        Config::set('rxnorm.circuit_breaker.failure_threshold', 2);
        Config::set('rxnorm.circuit_breaker.cooldown_seconds', 60);

        $this->logger->shouldReceive('error')->times(2);
        $this->logger->shouldReceive('warning')->once();

        $this->httpClient->shouldReceive('request')
            ->times(2)
            ->with('GET', 'https://rxnav.test/drugs.json', Mockery::type('array'))
            ->andThrow(
                new class('Server Error', 500) extends \Exception implements GuzzleException {},
                new class('Server Error', 500) extends \Exception implements GuzzleException {},
            );

        $this->cacheManager->shouldReceive('rememberSearch')
            ->times(3)
            ->with('aspirin', Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $closure) => $closure());

        $this->cacheManager->shouldReceive('rememberDrugDetails')->never();

        try {
            $this->service->searchDrugs('aspirin');
            $this->fail('First request should throw.');
        } catch (RxNormApiException $exception) {
            $this->assertSame('Failed to communicate with RxNorm API.', $exception->getMessage());
            $this->assertSame(500, $exception->statusCode());
        }

        try {
            $this->service->searchDrugs('aspirin');
            $this->fail('Second request should throw.');
        } catch (RxNormApiException $exception) {
            $this->assertSame('Failed to communicate with RxNorm API.', $exception->getMessage());
            $this->assertSame(500, $exception->statusCode());
        }

        $this->expectException(RxNormApiException::class);
        $this->expectExceptionMessage('RxNorm service is temporarily unavailable. Please try again later.');
        $this->expectExceptionCode(HttpResponse::HTTP_SERVICE_UNAVAILABLE);

        $this->service->searchDrugs('aspirin');
    }
}
