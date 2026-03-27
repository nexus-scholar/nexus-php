<?php

namespace Nexus\Core;

use Nexus\Config\NexusConfig;
use Nexus\Models\ProviderConfig;
use Nexus\Providers\BaseProvider;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\ArxivProvider;
use Nexus\Providers\SemanticScholarProvider;
use Nexus\Providers\CrossrefProvider;
use Nexus\Providers\PubMedProvider;
use Nexus\Providers\IEEEProvider;
use Nexus\Providers\DOAJProvider;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(string $name, array $options = []): BaseProvider
    {
        $config = new ProviderConfig(
            name: $name,
            enabled: $options['enabled'] ?? true,
            rateLimit: $options['rate_limit'] ?? 1.0,
            timeout: $options['timeout'] ?? 30,
            apiKey: $options['api_key'] ?? null,
            mailto: $options['mailto'] ?? null
        );

        return self::createProvider($name, $config);
    }

    public static function makeFromConfig(string $name, NexusConfig $config): BaseProvider
    {
        $settings = $config->getProviderSettings($name);

        $config = new ProviderConfig(
            name: $name,
            enabled: $settings?->enabled ?? true,
            rateLimit: $settings?->rateLimit ?? 1.0,
            timeout: $settings?->timeout ?? 30,
            apiKey: $settings?->apiKey ?? null,
            mailto: $config->mailto ?: null
        );

        return self::createProvider($name, $config);
    }

    /**
     * @return BaseProvider[]
     */
    public static function makeAllFromConfig(NexusConfig $config): array
    {
        $providers = [];
        foreach ($config->getEnabledProviders() as $name) {
            $providers[$name] = self::makeFromConfig($name, $config);
        }
        return $providers;
    }

    private static function createProvider(string $name, ProviderConfig $config): BaseProvider
    {
        return match ($name) {
            'openalex' => new OpenAlexProvider($config),
            'arxiv' => new ArxivProvider($config),
            's2' => new SemanticScholarProvider($config),
            'crossref' => new CrossrefProvider($config),
            'pubmed' => new PubMedProvider($config),
            'ieee' => new IEEEProvider($config),
            'doaj' => new DOAJProvider($config),
            default => throw new InvalidArgumentException("Unknown provider: {$name}"),
        };
    }
}
