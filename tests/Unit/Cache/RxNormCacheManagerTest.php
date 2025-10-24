<?php

namespace Tests\Unit\Cache;

use App\Cache\RxNormCacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RxNormCacheManagerTest extends TestCase
{
    private MockInterface $cache;

    private RxNormCacheManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('rxnorm.cache_ttl', 600);

        $this->cache = Mockery::mock(Repository::class);
        $this->manager = new RxNormCacheManager($this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_remembers_search_results(): void
    {
        $expectedKey = 'rxnorm:search:'.md5('aspirin');

        $this->cache->shouldReceive('remember')
            ->once()
            ->with($expectedKey, 600, Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $ttl, $closure) => $closure());

        $result = $this->manager->rememberSearch(' Aspirin ', static fn (): array => ['cached']);

        $this->assertSame(['cached'], $result);
    }

    public function test_it_returns_empty_array_when_search_callback_returns_null(): void
    {
        $expectedKey = 'rxnorm:search:'.md5('aspirin');

        $this->cache->shouldReceive('remember')
            ->once()
            ->with($expectedKey, 600, Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $ttl, $closure) => $closure());

        $result = $this->manager->rememberSearch('aspirin', static fn (): ?array => null);

        $this->assertSame([], $result);
    }

    public function test_it_remembers_drug_details(): void
    {
        $expectedKey = 'rxnorm:drug:198440';

        $this->cache->shouldReceive('remember')
            ->once()
            ->with($expectedKey, 600, Mockery::on(fn ($closure) => is_callable($closure)))
            ->andReturnUsing(fn ($key, $ttl, $closure) => $closure());

        $result = $this->manager->rememberDrugDetails('198440', static fn (): array => ['details']);

        $this->assertSame(['details'], $result);
    }

    public function test_it_forgets_cached_drug_details(): void
    {
        $expectedKey = 'rxnorm:drug:198440';

        $this->cache->shouldReceive('forget')
            ->once()
            ->with($expectedKey);

        $this->manager->forgetDrug('198440');

        $this->assertTrue(true);
    }
}
