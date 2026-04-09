<?php

declare(strict_types=1);

namespace Nexus\Utils;

class RateLimiter
{
    protected float $tokens;

    protected float $lastUpdate;

    protected object $lock;

    public function __construct(
        public readonly float $rate,
        public readonly int $capacity
    ) {
        if ($rate <= 0) {
            throw new \InvalidArgumentException("Rate must be positive, got {$rate}");
        }
        if ($capacity <= 0) {
            throw new \InvalidArgumentException("Capacity must be positive, got {$capacity}");
        }

        $this->tokens = (float) $capacity;
        $this->lastUpdate = microtime(true);
        $this->lock = new \stdClass;
    }

    public function consume(int $tokens = 1): bool
    {
        $lock = $this->lock;
        $result = false;

        $this->refill();

        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;
            $result = true;
        }

        return $result;
    }

    public function waitForToken(int $tokens = 1, ?float $timeout = null): bool
    {
        $startTime = microtime(true);

        while (true) {
            if ($this->consume($tokens)) {
                return true;
            }

            if ($timeout !== null) {
                $elapsed = microtime(true) - $startTime;
                if ($elapsed >= $timeout) {
                    return false;
                }
            }

            $this->refill();
            $deficit = $tokens - $this->tokens;
            $sleepTime = $deficit > 0 ? $deficit / $this->rate : 0.1;
            $sleepTime = min($sleepTime, 1.0);

            usleep((int) ($sleepTime * 1000000));
        }
    }

    protected function refill(): void
    {
        $now = microtime(true);
        $elapsed = $now - $this->lastUpdate;

        $newTokens = $elapsed * $this->rate;
        $this->tokens = min($this->capacity, $this->tokens + $newTokens);
        $this->lastUpdate = $now;
    }

    public function reset(): void
    {
        $this->tokens = (float) $this->capacity;
        $this->lastUpdate = microtime(true);
    }

    public function availableTokens(): float
    {
        $this->refill();

        return $this->tokens;
    }

    public function timeUntilTokens(int $tokens = 1): float
    {
        $this->refill();
        $deficit = $tokens - $this->tokens;
        if ($deficit <= 0) {
            return 0.0;
        }

        return $deficit / $this->rate;
    }
}
