<?php

declare(strict_types=1);

namespace Nexus\Utils;

use Closure;
use Throwable;
use Nexus\Utils\Exceptions\NetworkError;
use Nexus\Utils\Exceptions\RateLimitError;

class Retry
{
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly float $baseDelay = 1.0,
        public readonly float $backoffFactor = 2.0,
        public readonly float $maxDelay = 60.0,
        public readonly array $exceptions = [NetworkError::class, RateLimitError::class],
        public readonly ?Closure $onRetry = null
    ) {}

    public function execute(callable $operation, ...$args): mixed
    {
        $delay = $this->baseDelay;
        $lastException = null;

        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                return $operation(...$args);
            } catch (Throwable $e) {
                $lastException = $e;

        if (!$this->shouldRetry($e)) {
                throw $e;
            }

            if ($attempt < $this->maxRetries - 1) {
                $currentDelay = min($delay, $this->maxDelay);

                $this->callOnRetry($e, $attempt + 1);

                usleep((int) ($currentDelay * 1000000));
                $delay *= $this->backoffFactor;
            }
        }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \RuntimeException('Retry logic failed unexpectedly');
    }

    protected function shouldRetry(Throwable $e): bool
    {
        foreach ($this->exceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }
        return false;
    }

    public function callOnRetry(Throwable $e, int $attempt): void
    {
        if ($this->onRetry !== null) {
            ($this->onRetry)($e, $attempt);
        }
    }

    public static function withBackoff(
        int $maxRetries = 3,
        float $baseDelay = 1.0,
        float $backoffFactor = 2.0,
        float $maxDelay = 60.0,
        ?callable $onRetry = null
    ): self {
        return new self(
            maxRetries: $maxRetries,
            baseDelay: $baseDelay,
            backoffFactor: $backoffFactor,
            maxDelay: $maxDelay,
            onRetry: $onRetry !== null ? Closure::fromCallable($onRetry) : null
        );
    }

    public static function onRateLimit(
        int $maxRetries = 5,
        float $baseDelay = 5.0,
        float $backoffFactor = 2.0
    ): self {
        return new self(
            maxRetries: $maxRetries,
            baseDelay: $baseDelay,
            backoffFactor: $backoffFactor,
            maxDelay: 300.0,
            exceptions: [RateLimitError::class]
        );
    }
}
