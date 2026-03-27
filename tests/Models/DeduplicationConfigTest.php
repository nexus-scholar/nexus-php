<?php

namespace Nexus\Tests\Models;

use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use PHPUnit\Framework\TestCase;

class DeduplicationConfigTest extends TestCase
{
    public function test_default_config()
    {
        $config = new DeduplicationConfig();

        $this->assertEquals(DeduplicationStrategyName::CONSERVATIVE, $config->strategy);
        $this->assertEquals(97, $config->fuzzyThreshold);
        $this->assertEquals(1, $config->maxYearGap);
        $this->assertEquals(0.92, $config->semanticThreshold);
        $this->assertEquals('all-MiniLM-L6-v2', $config->embeddingModel);
        $this->assertFalse($config->useEmbeddings);
    }

    public function test_custom_config()
    {
        $config = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::SEMANTIC,
            fuzzyThreshold: 95,
            maxYearGap: 2,
            semanticThreshold: 0.90,
            useEmbeddings: true
        );

        $this->assertEquals(DeduplicationStrategyName::SEMANTIC, $config->strategy);
        $this->assertEquals(95, $config->fuzzyThreshold);
        $this->assertEquals(2, $config->maxYearGap);
        $this->assertEquals(0.90, $config->semanticThreshold);
        $this->assertTrue($config->useEmbeddings);
    }
}
