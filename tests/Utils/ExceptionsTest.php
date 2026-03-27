<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\Exceptions\SLRException;
use Nexus\Utils\Exceptions\ProviderError;
use Nexus\Utils\Exceptions\RateLimitError;
use Nexus\Utils\Exceptions\NetworkError;
use Nexus\Utils\Exceptions\AuthenticationError;
use Nexus\Utils\Exceptions\DeduplicationError;
use Nexus\Utils\Exceptions\ValidationError;
use Nexus\Utils\Exceptions\ExportError;
use Nexus\Utils\Exceptions\QueryError;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function testSlrExceptionCreation(): void
    {
        $exception = new SLRException('Test message', ['key' => 'value']);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(['key' => 'value'], $exception->details);
        $this->assertInstanceOf(\DateTimeImmutable::class, $exception->timestamp);
    }

    public function testSlrExceptionToString(): void
    {
        $exception = new SLRException('Test message', ['key' => 'value']);

        $result = (string) $exception;
        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('key=value', $result);
    }

    public function testSlrExceptionToArray(): void
    {
        $exception = new SLRException('Test message');

        $array = $exception->toArray();
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function testProviderError(): void
    {
        $exception = new ProviderError('openalex', 'Provider failed');

        $this->assertEquals('openalex', $exception->provider);
        $this->assertStringContainsString('openalex', $exception->getMessage());
        $this->assertStringContainsString('Provider failed', $exception->getMessage());
    }

    public function testRateLimitError(): void
    {
        $exception = new RateLimitError('crossref', 'Too many requests', 60);

        $this->assertEquals('crossref', $exception->provider);
        $this->assertEquals(60, $exception->retryAfter);
    }

    public function testNetworkError(): void
    {
        $exception = new NetworkError('arxiv', 'Connection timeout', 504);

        $this->assertEquals('arxiv', $exception->provider);
        $this->assertEquals(504, $exception->statusCode);
    }

    public function testAuthenticationError(): void
    {
        $exception = new AuthenticationError('openalex', 'Invalid API key');

        $this->assertEquals('openalex', $exception->provider);
        $this->assertStringContainsString('Invalid API key', $exception->getMessage());
    }

    public function testDeduplicationError(): void
    {
        $exception = new DeduplicationError('Failed to merge documents');

        $this->assertStringContainsString('Failed to merge documents', $exception->getMessage());
    }

    public function testValidationError(): void
    {
        $exception = new ValidationError('Invalid input', 'email');

        $this->assertEquals('email', $exception->field);
        $this->assertStringContainsString('Invalid input', $exception->getMessage());
    }

    public function testExportError(): void
    {
        $exception = new ExportError('Export failed', 'csv');

        $this->assertEquals('csv', $exception->format);
        $this->assertStringContainsString('Export failed', $exception->getMessage());
    }

    public function testQueryError(): void
    {
        $exception = new QueryError('Invalid query syntax', 'title:');

        $this->assertEquals('title:', $exception->query);
        $this->assertStringContainsString('Invalid query syntax', $exception->getMessage());
    }
}
