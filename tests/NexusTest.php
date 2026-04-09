<?php

namespace Nexus\Tests;

use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Providers\ArxivProvider;
use Nexus\Providers\CrossrefProvider;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\SemanticScholarProvider;
use PHPUnit\Framework\TestCase;

class NexusTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $service = new NexusService;
        $this->assertInstanceOf(NexusService::class, $service);
    }

    public function test_it_can_register_providers_via_factory()
    {
        $service = new NexusService;

        $providers = ['openalex', 'arxiv', 's2', 'crossref'];

        foreach ($providers as $name) {
            $provider = ProviderFactory::make($name, ['mailto' => 'test@example.com']);
            $service->registerProvider($provider);
            $this->assertEquals($name, $provider->getName());
        }
    }

    public function test_provider_classes()
    {
        $this->assertInstanceOf(OpenAlexProvider::class, ProviderFactory::make('openalex'));
        $this->assertInstanceOf(ArxivProvider::class, ProviderFactory::make('arxiv'));
        $this->assertInstanceOf(SemanticScholarProvider::class, ProviderFactory::make('s2'));
        $this->assertInstanceOf(CrossrefProvider::class, ProviderFactory::make('crossref'));
    }
}
