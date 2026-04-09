<?php

namespace Nexus\Config;

use Nexus\Models\DeduplicationConfig;

class NexusConfig
{
    /**
     * @param  array<string, ProviderSettings>  $providers
     */
    public function __construct(
        public string $mailto = '',
        public int $yearMin = 2020,
        public int $yearMax = 2026,
        public string $language = 'en',
        public array $providers = [],
        public DeduplicationConfig $deduplication = new DeduplicationConfig
    ) {}

    public function getProviderSettings(string $name): ?ProviderSettings
    {
        return $this->providers[$name] ?? null;
    }

    public function isProviderEnabled(string $name): bool
    {
        return $this->providers[$name]?->enabled ?? true;
    }

    public function getEnabledProviders(): array
    {
        $enabled = [];
        foreach ($this->providers as $name => $settings) {
            if ($settings->enabled) {
                $enabled[] = $name;
            }
        }

        return $enabled;
    }
}
