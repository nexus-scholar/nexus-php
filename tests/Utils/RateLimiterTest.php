<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    public function testConstruction(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $this->assertEquals(10.0, $limiter->rate);
        $this->assertEquals(20, $limiter->capacity);
    }

    public function testInvalidRate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RateLimiter(rate: 0, capacity: 10);
    }

    public function testInvalidCapacity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RateLimiter(rate: 10, capacity: 0);
    }

    public function testConsume(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $result = $limiter->consume(5);

        $this->assertTrue($result);
        $tokens = $limiter->availableTokens();
        $this->assertGreaterThan(14.9, $tokens);
        $this->assertLessThan(15.1, $tokens);
    }

    public function testConsumeInsufficientTokens(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $result = $limiter->consume(25);

        $this->assertFalse($result);
        $this->assertEquals(20.0, $limiter->availableTokens());
    }

    public function testReset(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $limiter->consume(10);
        $limiter->reset();

        $this->assertEquals(20.0, $limiter->availableTokens());
    }

    public function testTimeUntilTokens(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $limiter->consume(20);

        $time = $limiter->timeUntilTokens(10);

        $this->assertGreaterThan(0, $time);
        $this->assertLessThanOrEqual(1.0, $time);
    }

    public function testTimeUntilTokensAlreadyAvailable(): void
    {
        $limiter = new RateLimiter(rate: 10.0, capacity: 20);

        $time = $limiter->timeUntilTokens(5);

        $this->assertEquals(0.0, $time);
    }
}
