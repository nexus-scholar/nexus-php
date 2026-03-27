<?php

namespace Nexus\Laravel;

use Illuminate\Cache\RateLimiter as IlluminateRateLimiter;
use Illuminate\Http\Request;
use Nexus\Utils\Exceptions\RateLimitError;

class LaravelRateLimiter
{
    private IlluminateRateLimiter $limiter;

    public function __construct(IlluminateRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return $this->limiter->attempt(
            $key,
            $maxAttempts,
            function () {
                return true;
            },
            $decaySeconds
        );
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->limiter->tooManyAttempts($key, $maxAttempts);
    }

    public function hit(string $key, int $decaySeconds = 60): int
    {
        return $this->limiter->hit($key, $decaySeconds);
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    public function clear(string $key): void
    {
        $this->limiter->clear($key);
    }

    public function availableIn(string $key): int
    {
        return $this->limiter->availableIn($key);
    }

    public function waitTime(string $key): int
    {
        return $this->limiter->availableIn($key);
    }

    public function getProviderKey(string $provider, ?string $ip = null): string
    {
        $base = "nexus:{$provider}:rate_limit";
        if ($ip !== null) {
            return "{$base}:{$ip}";
        }
        return $base;
    }

    public function waitForProvider(string $provider, float $rateLimit, ?string $ip = null): void
    {
        $key = $this->getProviderKey($provider, $ip);
        $maxAttempts = (int) floor($rateLimit);

        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }

        if ($this->tooManyAttempts($key, $maxAttempts)) {
            $waitTime = $this->waitTime($key);
            if ($waitTime > 0) {
                usleep($waitTime * 1000000);
            }
        }

        $this->hit($key, 1);
    }

    public function waitForProviders(array $providers, array $rateLimits, ?string $ip = null): void
    {
        foreach ($providers as $provider) {
            $rateLimit = $rateLimits[$provider] ?? 1.0;
            $this->waitForProvider($provider, $rateLimit, $ip);
        }
    }
}
