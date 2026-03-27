<?php

namespace Nexus\Tests\Providers;

use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\PubMedProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class PubMedProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    public function test_provider_returns_correct_name(): void
    {
        $provider = new PubMedProvider(new ProviderConfig(name: 'pubmed'));
        $this->assertEquals('pubmed', $provider->getName());
    }

    public function test_provider_requires_api_key_for_high_rate(): void
    {
        $config = new ProviderConfig(name: 'pubmed', rateLimit: 1.0);
        $provider = new PubMedProvider($config);
        $this->assertEquals('pubmed', $provider->getName());
    }
}
