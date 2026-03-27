<?php

namespace Nexus\Tests\Models;

use Nexus\Models\ProviderConfig;
use PHPUnit\Framework\TestCase;

class ProviderConfigTest extends TestCase
{
    public function test_default_config()
    {
        $config = new ProviderConfig(name: 'openalex');

        $this->assertEquals('openalex', $config->name);
        $this->assertTrue($config->enabled);
        $this->assertEquals(1.0, $config->rateLimit);
        $this->assertEquals(30, $config->timeout);
        $this->assertNull($config->apiKey);
        $this->assertNull($config->mailto);
    }

    public function test_custom_config()
    {
        $config = new ProviderConfig(
            name: 'crossref',
            enabled: false,
            rateLimit: 2.0,
            timeout: 60,
            apiKey: 'secret-key-123',
            mailto: 'test@example.com'
        );

        $this->assertEquals('crossref', $config->name);
        $this->assertFalse($config->enabled);
        $this->assertEquals(2.0, $config->rateLimit);
        $this->assertEquals(60, $config->timeout);
        $this->assertEquals('secret-key-123', $config->apiKey);
        $this->assertEquals('test@example.com', $config->mailto);
    }
}
