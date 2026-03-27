<?php

namespace Nexus\Models;

class ProviderConfig
{
    public function __construct(
        public string $name,
        public bool $enabled = true,
        public float $rateLimit = 1.0,
        public int $timeout = 30,
        public ?string $apiKey = null,
        public ?string $mailto = null
    ) {}
}
