<?php

namespace Nexus\Tests\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\DOAJProvider;
use PHPUnit\Framework\TestCase;

class DOAJProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    public function test_provider_returns_correct_name(): void
    {
        $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'));
        $this->assertEquals('doaj', $provider->getName());
    }

    public function test_search_handles_empty_results(): void
    {
        $response = json_encode(['results' => []]);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_returns_documents(): void
    {
        $responseData = [
            'results' => [
                [
                    'id' => 'doaj_12345',
                    'bibjson' => [
                        'title' => 'Test Paper',
                        'year' => '2023',
                        'abstract' => 'Test abstract',
                        'author' => [['name' => 'John Smith']],
                        'journal' => ['title' => 'Test Journal'],
                        'identifier' => [
                            ['type' => 'doi', 'id' => '10.1234/test'],
                            ['type' => 'url', 'id' => 'https://example.com/paper'],
                        ],
                    ],
                ],
            ],
            'total' => 1,
        ];
        $response = json_encode($responseData);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);

        $query = new Query(text: 'test');
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertEquals('Test Paper', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals('10.1234/test', $docs[0]->externalIds->doi);
    }

    public function test_search_extracts_authors(): void
    {
        $responseData = [
            'results' => [
                [
                    'bibjson' => [
                        'title' => 'Test Paper',
                        'year' => '2023',
                        'author' => [
                            ['name' => 'John Smith'],
                            ['name' => 'Jane Doe'],
                        ],
                    ],
                ],
            ],
            'total' => 1,
        ];
        $response = json_encode($responseData);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);

        $query = new Query(text: 'test');
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Smith', $docs[0]->authors[0]->familyName);
    }
}
