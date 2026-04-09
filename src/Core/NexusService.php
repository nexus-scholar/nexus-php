<?php

namespace Nexus\Core;

use Generator;
use Nexus\Models\Query;
use Nexus\Providers\BaseProvider;

class NexusService
{
    /**
     * @var BaseProvider[]
     */
    private array $providers = [];

    public function registerProvider(BaseProvider $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    public function clearProviders(): void
    {
        $this->providers = [];
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param  string[]|null  $providerNames
     */
    public function search(Query $query, ?array $providerNames = null): Generator
    {
        $targets = $providerNames
            ? array_intersect_key($this->providers, array_flip($providerNames))
            : $this->providers;

        foreach ($targets as $provider) {
            yield from $provider->search($query);
        }
    }
}
