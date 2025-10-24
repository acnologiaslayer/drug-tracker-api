<?php

namespace App\Services;

use App\Cache\RxNormCacheManager;
use App\Exceptions\RxNormApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RxNormService
{
    private const DRUGS_ENDPOINT = 'drugs.json';
    private const RXCUI_HISTORY_ENDPOINT = 'rxcui/%s/historystatus.json';
    private const RXCUI_VALIDATE_ENDPOINT = 'rxcui/%s.json';
    private const CIRCUIT_FAILURES_KEY = 'rxnorm:circuit:failures';
    private const CIRCUIT_OPEN_UNTIL_KEY = 'rxnorm:circuit:open_until';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RxNormCacheManager $cacheManager,
        private readonly LoggerInterface $logger,
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchDrugs(string $drugName): array
    {
        $query = trim($drugName);

        if ($query === '') {
            return [];
        }

        return $this->cacheManager->rememberSearch($query, function () use ($query): array {
            $response = $this->request(self::DRUGS_ENDPOINT, [
                'query' => [
                    'name' => $query,
                    'tty' => 'SBD',
                ],
            ]);

            $conceptGroups = Arr::get($response, 'drugGroup.conceptGroup', []);
            $properties = collect($conceptGroups)
                ->filter(fn (array $group) => Arr::get($group, 'tty') === 'SBD')
                ->flatMap(fn (array $group) => Arr::get($group, 'conceptProperties', []))
                ->take(5)
                ->values();

            return $properties->map(function (array $concept) {
                $rxcui = (string) Arr::get($concept, 'rxcui');
                $details = $this->getDrugDetails($rxcui);

                return [
                    'rxcui' => $rxcui,
                    'name' => (string) Arr::get($concept, 'name'),
                    'ingredient_base_names' => Arr::get($details, 'base_names', []),
                    'dosage_forms' => Arr::get($details, 'dose_forms', []),
                ];
            })->all();
        });
    }

    /**
     * @return array{base_names: array<int, string>, dose_forms: array<int, string>}|array<string, array>
     */
    public function getDrugDetails(string $rxcui): array
    {
        $rxcui = trim($rxcui);

        if ($rxcui === '') {
            return ['base_names' => [], 'dose_forms' => []];
        }

        return $this->cacheManager->rememberDrugDetails($rxcui, function () use ($rxcui): array {
            $endpoint = sprintf(self::RXCUI_HISTORY_ENDPOINT, $rxcui);
            $response = $this->request($endpoint);
            $derived = Arr::get($response, 'rxcuiHistoryStatus.derivedConcepts', []);

            $baseNames = collect(Arr::get($derived, 'ingredientAndStrength', []))
                ->map(fn (array $ingredient) => (string) Arr::get($ingredient, 'baseName'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $doseForms = collect(Arr::get($derived, 'doseFormGroupConcept', []))
                ->map(fn (array $concept) => (string) Arr::get($concept, 'doseFormGroupName'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $name = $this->fetchDrugName($rxcui);

            return [
                'name' => $name,
                'base_names' => $baseNames,
                'dose_forms' => $doseForms,
            ];
        });
    }

    public function validateRxcui(string $rxcui): bool
    {
        $rxcui = trim($rxcui);

        if ($rxcui === '') {
            return false;
        }

        try {
            $endpoint = sprintf(self::RXCUI_VALIDATE_ENDPOINT, $rxcui);
            $response = $this->request($endpoint);
        } catch (RxNormApiException $exception) {
            if ($exception->statusCode() === 404) {
                return false;
            }

            throw $exception;
        }

        $ids = Arr::get($response, 'idGroup.rxnormId');

        if (is_array($ids)) {
            $stringIds = array_map(static fn ($id) => (string) $id, $ids);

            return in_array($rxcui, $stringIds, true);
        }

        if (is_string($ids) || is_int($ids)) {
            return (string) $ids === $rxcui;
        }

        $name = (string) Arr::get($response, 'idGroup.name', '');

        return trim($name) !== '';
    }

    private function fetchDrugName(string $rxcui): string
    {
        $endpoint = sprintf(self::RXCUI_VALIDATE_ENDPOINT, $rxcui);
        $response = $this->request($endpoint);

        $name = Arr::get($response, 'idGroup.name');

        return is_string($name) ? $name : '';
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $path, array $options = []): array
    {
        $url = rtrim((string) config('rxnorm.base_url'), '/') . '/' . ltrim($path, '/');
        $options['timeout'] = (int) config('rxnorm.timeout', 10);

        if ($this->isCircuitOpen()) {
            throw new RxNormApiException(
                'RxNorm service is temporarily unavailable. Please try again later.',
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        $retryConfig = (array) config('rxnorm.retry', []);
        $attempts = max(1, (int) ($retryConfig['attempts'] ?? 3));
        $delayMs = max(0, (int) ($retryConfig['delay_ms'] ?? 200));
        $multiplier = max(1, (float) ($retryConfig['backoff_multiplier'] ?? 2));

        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->httpClient->request('GET', $url, $options);
                $contents = (string) $response->getBody();

                if ($contents === '') {
                    $this->resetCircuitBreaker();

                    return [];
                }

                /** @var array<string, mixed> $decoded */
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

                $this->resetCircuitBreaker();

                return $decoded;
            } catch (GuzzleException $exception) {
                $statusCode = (int) $exception->getCode();

                $this->logger->error('RxNorm API request failed.', [
                    'message' => $exception->getMessage(),
                    'url' => $url,
                    'options' => $options,
                    'status_code' => $statusCode,
                    'attempt' => $attempt,
                ]);

                $lastException = new RxNormApiException(
                    'Failed to communicate with RxNorm API.',
                    $statusCode,
                    $exception,
                );

                if ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR || $statusCode === 0) {
                    $this->recordFailure();
                } else {
                    break;
                }
            } catch (Throwable $exception) {
                $this->logger->error('RxNorm API response parsing failed.', [
                    'message' => $exception->getMessage(),
                    'url' => $url,
                    'attempt' => $attempt,
                ]);

                $lastException = new RxNormApiException(
                    'Unexpected response from RxNorm API.',
                    0,
                    $exception,
                );

                $this->recordFailure();
            }

            if ($attempt < $attempts) {
                $waitMs = (int) round($delayMs * ($multiplier ** ($attempt - 1)));

                if ($waitMs > 0) {
                    usleep($waitMs * 1000);
                }
            }
        }

        throw $lastException ?? new RxNormApiException('Failed to communicate with RxNorm API.');
    }

    private function isCircuitOpen(): bool
    {
        $openUntil = (int) $this->cache->get(self::CIRCUIT_OPEN_UNTIL_KEY, 0);

        if ($openUntil === 0) {
            return false;
        }

        if ($openUntil > time()) {
            return true;
        }

        $this->resetCircuitBreaker();

        return false;
    }

    private function recordFailure(): void
    {
        $failureThreshold = max(1, (int) config('rxnorm.circuit_breaker.failure_threshold', 5));
        $cooldownSeconds = max(1, (int) config('rxnorm.circuit_breaker.cooldown_seconds', 60));

        $failures = (int) $this->cache->get(self::CIRCUIT_FAILURES_KEY, 0) + 1;
        $this->cache->put(self::CIRCUIT_FAILURES_KEY, $failures, $cooldownSeconds);

        if ($failures >= $failureThreshold) {
            $openUntil = time() + $cooldownSeconds;
            $this->cache->put(self::CIRCUIT_OPEN_UNTIL_KEY, $openUntil, $cooldownSeconds);

            $this->logger->warning('RxNorm circuit breaker opened.', [
                'failures' => $failures,
                'cooldown_seconds' => $cooldownSeconds,
            ]);
        }
    }

    private function resetCircuitBreaker(): void
    {
        $this->cache->forget(self::CIRCUIT_FAILURES_KEY);
        $this->cache->forget(self::CIRCUIT_OPEN_UNTIL_KEY);
    }
}
