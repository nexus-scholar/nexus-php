<?php

namespace Nexus\Tests\Core;

use InvalidArgumentException;
use Nexus\Core\ProviderFactory;
use Nexus\Providers\ArxivProvider;
use Nexus\Providers\CrossrefProvider;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\SemanticScholarProvider;
use PHPUnit\Framework\TestCase;

class ProviderFactoryTest extends TestCase
{
    public function test_make_openalex_provider()
    {
        $provider = ProviderFactory::make('openalex', ['mailto' => 'test@example.com']);

        $this->assertInstanceOf(OpenAlexProvider::class, $provider);
        $this->assertEquals('openalex', $provider->getName());
    }

    public function test_make_arxiv_provider()
    {
        $provider = ProviderFactory::make('arxiv');

        $this->assertInstanceOf(ArxivProvider::class, $provider);
        $this->assertEquals('arxiv', $provider->getName());
    }

    public function test_make_crossref_provider()
    {
        $provider = ProviderFactory::make('crossref');

        $this->assertInstanceOf(CrossrefProvider::class, $provider);
        $this->assertEquals('crossref', $provider->getName());
    }

    public function test_make_semantic_scholar_provider()
    {
        $provider = ProviderFactory::make('s2');

        $this->assertInstanceOf(SemanticScholarProvider::class, $provider);
        $this->assertEquals('s2', $provider->getName());
    }

    public function test_make_unknown_provider_throws()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider: unknown');

        ProviderFactory::make('unknown');
    }

    public function test_make_with_custom_config()
    {
        $provider = ProviderFactory::make('openalex', [
            'mailto' => 'test@example.com',
            'timeout' => 60,
            'api_key' => 'secret-key',
            'rate_limit' => 5.0,
        ]);

        $this->assertInstanceOf(OpenAlexProvider::class, $provider);
    }
}
