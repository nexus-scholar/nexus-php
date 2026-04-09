<?php

namespace Nexus\Laravel;

use Nexus\Config\NexusConfig as BaseConfig;
use Nexus\Config\ProviderSettings;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;

class NexusConfig extends BaseConfig
{
    public int $cache_ttl = 3600;

    public string $cache_store = 'default';

    public int $rate_limiter_attempts = 60;

    public int $rate_limiter_decay_seconds = 60;

    public string $queue_connection = 'default';

    public string $queue_name = 'nexus';

    public bool $logging_enabled = true;

    public function __construct(array $laravelConfig = [])
    {
        if (! empty($laravelConfig)) {
            $this->loadFromLaravelConfig($laravelConfig);
        } else {
            parent::__construct();
        }
    }

    private function loadFromLaravelConfig(array $config): void
    {
        $this->mailto = $config['mailto'] ?? '';
        $this->yearMin = (int) ($config['year_min'] ?? 2020);
        $this->yearMax = (int) ($config['year_max'] ?? 2026);
        $this->language = $config['language'] ?? 'en';

        $this->cache_ttl = (int) ($config['cache']['ttl'] ?? 3600);
        $this->cache_store = $config['cache']['store'] ?? 'default';

        $this->rate_limiter_attempts = (int) ($config['rate_limiter']['attempts'] ?? 60);
        $this->rate_limiter_decay_seconds = (int) ($config['rate_limiter']['decay_seconds'] ?? 60);

        $this->queue_connection = $config['queue']['connection'] ?? 'default';
        $this->queue_name = $config['queue']['name'] ?? 'nexus';

        $this->logging_enabled = (bool) ($config['logging']['enabled'] ?? true);

        $providers = [];
        foreach ($config['providers'] ?? [] as $name => $settings) {
            $providers[$name] = ProviderSettings::fromArray($settings);
        }
        $this->providers = $providers;

        $this->deduplication = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::from($config['deduplication']['strategy'] ?? 'conservative'),
            fuzzyThreshold: (int) ($config['deduplication']['fuzzy_threshold'] ?? 97),
            maxYearGap: (int) ($config['deduplication']['max_year_gap'] ?? 1)
        );
    }

    public static function fromLaravelConfig(): self
    {
        $laravelConfig = config('nexus', []);

        return new self($laravelConfig);
    }
}
