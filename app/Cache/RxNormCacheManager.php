<?php

namespace App\Cache;

use Closure;
use Illuminate\Cache\Repository;

class RxNormCacheManager
{
    public function __construct(private readonly Repository $cache)
    {
    }

    public function rememberSearch(string $query, Closure $callback): array
    {
        $key = $this->searchKey($query);

        return $this->cache->remember($key, config('rxnorm.cache_ttl'), static function () use ($callback) {
            return $callback() ?? [];
        });
    }

    public function rememberDrugDetails(string $rxcui, Closure $callback): array
    {
        $key = $this->drugKey($rxcui);

        return $this->cache->remember($key, config('rxnorm.cache_ttl'), static function () use ($callback) {
            return $callback() ?? [];
        });
    }

    public function forgetDrug(string $rxcui): void
    {
        $this->cache->forget($this->drugKey($rxcui));
    }

    private function searchKey(string $query): string
    {
        return 'rxnorm:search:' . md5(mb_strtolower(trim($query)));
    }

    private function drugKey(string $rxcui): string
    {
        return "rxnorm:drug:{$rxcui}";
    }
}
