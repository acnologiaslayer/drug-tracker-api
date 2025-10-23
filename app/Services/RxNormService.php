<?php

namespace App\Services;

use App\Cache\RxNormCacheManager;
use App\Exceptions\RxNormApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Throwable;

class RxNormService
{
    private const DRUGS_ENDPOINT = 'drugs.json';
    private const RXCUI_HISTORY_ENDPOINT = 'rxcui/%s/historystatus.json';
    private const RXCUI_VALIDATE_ENDPOINT = 'rxcui/%s.json';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RxNormCacheManager $cacheManager,
        private readonly LoggerInterface $logger,
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
            return in_array($rxcui, $ids, true);
        }

        if (is_string($ids)) {
            return $ids === $rxcui;
        }

        return false;
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
        $url = rtrim(config('rxnorm.base_url'), '/') . '/' . ltrim($path, '/');
        $options['timeout'] = (int) config('rxnorm.timeout');

        try {
            $response = $this->httpClient->request('GET', $url, $options);
            $contents = (string) $response->getBody();

            if ($contents === '') {
                return [];
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (GuzzleException $exception) {
            $code = $exception->getCode();

            $this->logger->error('RxNorm API request failed.', [
                'message' => $exception->getMessage(),
                'url' => $url,
                'options' => $options,
                'status_code' => $code,
            ]);

            throw new RxNormApiException('Failed to communicate with RxNorm API.', $code, $exception);
        } catch (Throwable $exception) {
            $this->logger->error('RxNorm API response parsing failed.', [
                'message' => $exception->getMessage(),
                'url' => $url,
            ]);

            throw new RxNormApiException('Unexpected response from RxNorm API.', 0, $exception);
        }
    }
}
