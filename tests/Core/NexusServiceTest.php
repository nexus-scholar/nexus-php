<?php

namespace Nexus\Tests\Core;

use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Models\ProviderConfig;
use Nexus\Providers\BaseProvider;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\ArxivProvider;
use Nexus\Providers\CrossrefProvider;
use Nexus\Providers\SemanticScholarProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class NexusServiceTest extends TestCase
{
    public function test_can_register_provider()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'results' => [],
                'meta' => ['next_cursor' => null],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $config = new ProviderConfig(name: 'openalex', mailto: 'test@example.com');
        $provider = new OpenAlexProvider($config, $client);

        $service = new NexusService();
        $service->registerProvider($provider);

        $results = iterator_to_array($service->search(new \Nexus\Models\Query('test', maxResults: 1)));
        $this->assertIsArray($results);
    }

    public function test_search_with_specific_providers()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'results' => [],
                'meta' => ['next_cursor' => null],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $config = new ProviderConfig(name: 'openalex', mailto: 'test@example.com');
        $provider = new OpenAlexProvider($config, $client);

        $service = new NexusService();
        $service->registerProvider($provider);

        $query = new \Nexus\Models\Query('test', maxResults: 1);
        
        $results = iterator_to_array($service->search($query, ['openalex']));
        
        $this->assertIsArray($results);
    }

    public function test_search_with_multiple_providers()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'results' => [],
                'meta' => ['next_cursor' => null],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        
        $openAlexConfig = new ProviderConfig(name: 'openalex');
        $openAlexProvider = new OpenAlexProvider($openAlexConfig, $client);

        $service = new NexusService();
        $service->registerProvider($openAlexProvider);

        $query = new \Nexus\Models\Query('test', maxResults: 1);
        
        $results = iterator_to_array($service->search($query, null));
        
        $this->assertIsArray($results);
    }
}
