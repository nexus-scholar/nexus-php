<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\Exceptions\NetworkError;
use Nexus\Utils\Exceptions\RateLimitError;
use Nexus\Utils\Retry;
use PHPUnit\Framework\TestCase;

class RetryTest extends TestCase
{
    public function test_successful_operation(): void
    {
        $retry = new Retry(maxRetries: 3);

        $result = $retry->execute(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    public function test_retries_on_network_error(): void
    {
        $retry = new Retry(maxRetries: 3, baseDelay: 0.01);
        $attempts = 0;

        $result = $retry->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new NetworkError('test', 'Network error');
            }

            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }

    public function test_exhausts_retries(): void
    {
        $retry = new Retry(maxRetries: 2, baseDelay: 0.01);

        $this->expectException(NetworkError::class);

        $retry->execute(function () {
            throw new NetworkError('test', 'Network error');
        });
    }

    public function test_does_not_retry_non_configured_exception(): void
    {
        $retry = new Retry(maxRetries: 3, exceptions: [RateLimitError::class]);

        $this->expectException(\InvalidArgumentException::class);

        $retry->execute(function () {
            throw new \InvalidArgumentException('Invalid');
        });
    }

    public function test_static_factory_on_rate_limit(): void
    {
        $retry = Retry::onRateLimit(maxRetries: 3);

        $this->assertEquals(3, $retry->maxRetries);
        $this->assertEquals(5.0, $retry->baseDelay);
        $this->assertEquals(2.0, $retry->backoffFactor);
        $this->assertEquals(300.0, $retry->maxDelay);
        $this->assertContains(RateLimitError::class, $retry->exceptions);
    }

    public function test_on_retry_callback_is_called(): void
    {
        $callbackCalled = false;

        $retry = new Retry(
            maxRetries: 3,
            baseDelay: 0.01,
            onRetry: function ($e, $attempt) use (&$callbackCalled) {
                $callbackCalled = true;

                return [$e, $attempt];
            }
        );

        $attempts = 0;

        $retry->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new NetworkError('test', 'Error');
            }

            return 'success';
        });

        $this->assertTrue($callbackCalled);
        $this->assertEquals(3, $attempts);
    }
}
