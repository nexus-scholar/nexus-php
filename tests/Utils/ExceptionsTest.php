<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\Exceptions\AuthenticationError;
use Nexus\Utils\Exceptions\DeduplicationError;
use Nexus\Utils\Exceptions\ExportError;
use Nexus\Utils\Exceptions\NetworkError;
use Nexus\Utils\Exceptions\ProviderError;
use Nexus\Utils\Exceptions\QueryError;
use Nexus\Utils\Exceptions\RateLimitError;
use Nexus\Utils\Exceptions\SLRException;
use Nexus\Utils\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_slr_exception_creation(): void
    {
        $exception = new SLRException('Test message', ['key' => 'value']);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(['key' => 'value'], $exception->details);
        $this->assertInstanceOf(\DateTimeImmutable::class, $exception->timestamp);
    }

    public function test_slr_exception_to_string(): void
    {
        $exception = new SLRException('Test message', ['key' => 'value']);

        $result = (string) $exception;
        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('key=value', $result);
    }

    public function test_slr_exception_to_array(): void
    {
        $exception = new SLRException('Test message');

        $array = $exception->toArray();
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function test_provider_error(): void
    {
        $exception = new ProviderError('openalex', 'Provider failed');

        $this->assertEquals('openalex', $exception->provider);
        $this->assertStringContainsString('openalex', $exception->getMessage());
        $this->assertStringContainsString('Provider failed', $exception->getMessage());
    }

    public function test_rate_limit_error(): void
    {
        $exception = new RateLimitError('crossref', 'Too many requests', 60);

        $this->assertEquals('crossref', $exception->provider);
        $this->assertEquals(60, $exception->retryAfter);
    }

    public function test_network_error(): void
    {
        $exception = new NetworkError('arxiv', 'Connection timeout', 504);

        $this->assertEquals('arxiv', $exception->provider);
        $this->assertEquals(504, $exception->statusCode);
    }

    public function test_authentication_error(): void
    {
        $exception = new AuthenticationError('openalex', 'Invalid API key');

        $this->assertEquals('openalex', $exception->provider);
        $this->assertStringContainsString('Invalid API key', $exception->getMessage());
    }

    public function test_deduplication_error(): void
    {
        $exception = new DeduplicationError('Failed to merge documents');

        $this->assertStringContainsString('Failed to merge documents', $exception->getMessage());
    }

    public function test_validation_error(): void
    {
        $exception = new ValidationError('Invalid input', 'email');

        $this->assertEquals('email', $exception->field);
        $this->assertStringContainsString('Invalid input', $exception->getMessage());
    }

    public function test_export_error(): void
    {
        $exception = new ExportError('Export failed', 'csv');

        $this->assertEquals('csv', $exception->format);
        $this->assertStringContainsString('Export failed', $exception->getMessage());
    }

    public function test_query_error(): void
    {
        $exception = new QueryError('Invalid query syntax', 'title:');

        $this->assertEquals('title:', $exception->query);
        $this->assertStringContainsString('Invalid query syntax', $exception->getMessage());
    }
}
