<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class NetworkError extends ProviderError
{
    public readonly ?int $statusCode;

    public function __construct(string $provider, string $message = 'Network error', ?int $statusCode = null, array $details = [])
    {
        if ($statusCode !== null) {
            $details['statusCode'] = $statusCode;
        }
        parent::__construct($provider, $message, $details);
        $this->statusCode = $statusCode;
    }
}
