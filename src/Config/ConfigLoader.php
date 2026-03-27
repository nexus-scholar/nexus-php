<?php

namespace Nexus\Config;

use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Utils\Exceptions\ConfigurationError;

class ConfigLoader
{
    public static function loadFromFile(string $path): NexusConfig
    {
        if (!file_exists($path)) {
            throw new ConfigurationError("Config file not found: {$path}");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'php') {
            return self::loadFromPhp($path);
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new ConfigurationError("Failed to read config file: {$path}");
        }

        return self::loadFromJson($content);
    }

    public static function loadFromPhp(string $path): NexusConfig
    {
        $data = require $path;
        if (!is_array($data)) {
            throw new ConfigurationError("PHP config must return an array");
        }
        return self::loadFromArray($data);
    }

    public static function loadFromJson(string $json): NexusConfig
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConfigurationError("Invalid JSON: " . json_last_error_msg());
        }

        return self::loadFromArray($data);
    }

    public static function loadFromArray(array $data): NexusConfig
    {
        $providers = [];
        foreach ($data['providers'] ?? [] as $name => $settings) {
            $providers[$name] = ProviderSettings::fromArray($settings);
        }

        $deduplication = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::from($data['deduplication']['strategy'] ?? 'conservative'),
            fuzzyThreshold: (int) ($data['deduplication']['fuzzy_threshold'] ?? 97),
            maxYearGap: (int) ($data['deduplication']['max_year_gap'] ?? 1)
        );

        return new NexusConfig(
            mailto: $data['mailto'] ?? '',
            yearMin: (int) ($data['year_min'] ?? 2020),
            yearMax: (int) ($data['year_max'] ?? 2026),
            language: $data['language'] ?? 'en',
            providers: $providers,
            deduplication: $deduplication
        );
    }

    public static function loadDefault(): NexusConfig
    {
        $defaultPath = __DIR__ . '/../../config/nexus.php';
        if (file_exists($defaultPath)) {
            return self::loadFromFile($defaultPath);
        }

        $jsonPath = __DIR__ . '/../../config/nexus.json';
        if (file_exists($jsonPath)) {
            return self::loadFromFile($jsonPath);
        }

        throw new ConfigurationError("No config file found. Please create config/nexus.php or config/nexus.json");
    }

    public static function getConfigPath(?string $configPath = null): string
    {
        if ($configPath !== null) {
            return $configPath;
        }

        $paths = [
            getcwd() . '/config/nexus.json',
            getcwd() . '/config/nexus.php',
            __DIR__ . '/../../config/nexus.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return realpath($path) ?: $path;
            }
        }

        return __DIR__ . '/../../config/nexus.php';
    }
}
