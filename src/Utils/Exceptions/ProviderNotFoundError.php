<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ProviderNotFoundError extends ProviderError
{
    public function __construct(string $provider, string $message = "Provider not found", array $details = [])
    {
        parent::__construct($provider, $message, $details);
    }
}
