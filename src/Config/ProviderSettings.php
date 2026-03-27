<?php

namespace Nexus\Config;

class ProviderSettings
{
    public function __construct(
        public bool $enabled = true,
        public float $rateLimit = 1.0,
        public int $timeout = 30,
        public ?string $apiKey = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: $data['enabled'] ?? true,
            rateLimit: (float) ($data['rate_limit'] ?? 1.0),
            timeout: (int) ($data['timeout'] ?? 30),
            apiKey: $data['api_key'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'rate_limit' => $this->rateLimit,
            'timeout' => $this->timeout,
            'api_key' => $this->apiKey,
        ];
    }
}
