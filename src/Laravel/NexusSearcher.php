<?php

namespace Nexus\Laravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Laravel\Jobs\SearchJob;
use Nexus\Models\Document;
use Nexus\Models\Query;

class NexusSearcher
{
    public function __construct(
        private NexusService $service,
        private NexusConfig $config,
        private CacheRepository $cache
    ) {}

    public function search(Query $query, ?array $providers = null, bool $useCache = true): array
    {
        $cacheKey = $this->getCacheKey($query, $providers);

        if ($useCache && $providers === null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $this->service->clearProviders();

        $providerNames = $providers ?? $this->config->getEnabledProviders();
        foreach ($providerNames as $name) {
            if ($name !== 'core') {
                $provider = ProviderFactory::makeFromConfig($name, $this->config);
                $this->service->registerProvider($provider);
            }
        }

        $results = iterator_to_array($this->service->search($query));

        if ($useCache && $providers === null) {
            $ttl = $this->config->cache_ttl ?? 3600;
            $this->cache->put($cacheKey, $results, $ttl);
        }

        return $results;
    }

    public function searchAsync(Query $query, ?array $providers = null, ?string $queue = null): string
    {
        return SearchJob::dispatch($query, $providers ?? $this->config->getEnabledProviders())
            ->onQueue($queue ?? 'nexus')
            ->id;
    }

    private function getCacheKey(Query $query, ?array $providers): string
    {
        $key = 'nexus:search:' . md5($query->text . ':' . ($query->yearMin ?? '') . ':' . ($query->yearMax ?? ''));

        if ($providers !== null) {
            $key .= ':' . implode(',', $providers);
        }

        return $key;
    }

    public function clearCache(Query $query, ?array $providers = null): bool
    {
        return $this->cache->forget($this->getCacheKey($query, $providers));
    }

    public function clearAllCache(): bool
    {
        $tags = $this->cache->tags(['nexus-search']);
        $tags?->flush();
        return true;
    }
}
