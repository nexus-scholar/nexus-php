<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ProviderConfigError extends ProviderError
{
    public function __construct(string $provider, string $message = 'Invalid configuration', array $details = [])
    {
        parent::__construct($provider, $message, $details);
    }
}
