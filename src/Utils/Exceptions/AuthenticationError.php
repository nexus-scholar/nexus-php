<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class AuthenticationError extends ProviderError
{
    public function __construct(string $provider, string $message = 'Authentication failed', array $details = [])
    {
        parent::__construct($provider, $message, $details);
    }
}
