<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ProviderError extends SLRException
{
    public readonly string $provider;

    public function __construct(string $provider, string $message, array $details = [])
    {
        parent::__construct("[{$provider}] {$message}", $details);
        $this->provider = $provider;
    }
}
