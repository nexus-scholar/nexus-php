<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class RateLimitError extends ProviderError
{
    public readonly ?int $retryAfter;

    public function __construct(string $provider, string $message = "Rate limit exceeded", ?int $retryAfter = null, array $details = [])
    {
        if ($retryAfter !== null) {
            $details['retryAfter'] = $retryAfter;
        }
        parent::__construct($provider, $message, $details);
        $this->retryAfter = $retryAfter;
    }
}
