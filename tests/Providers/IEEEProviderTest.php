<?php

namespace Nexus\Tests\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\IEEEProvider;
use Nexus\Utils\Exceptions\AuthenticationError;
use PHPUnit\Framework\TestCase;

class IEEEProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    public function test_provider_returns_correct_name(): void
    {
        $provider = new IEEEProvider(new ProviderConfig(name: 'ieee'));
        $this->assertEquals('ieee', $provider->getName());
    }

    public function test_provider_requires_api_key(): void
    {
        $this->expectException(AuthenticationError::class);

        $provider = new IEEEProvider(new ProviderConfig(name: 'ieee'));
        $query = new Query(text: 'test');
        iterator_to_array($provider->search($query));
    }

    public function test_search_handles_empty_results(): void
    {
        $response = json_encode(['articles' => []]);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_returns_documents(): void
    {
        $responseData = [
            'articles' => [
                [
                    'title' => 'Test Paper',
                    'publication_year' => 2023,
                    'doi' => '10.1234/test',
                    'authors' => ['authors' => [['full_name' => 'John Smith']]],
                ],
            ],
            'total_records' => 1,
        ];
        $response = json_encode($responseData);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);

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
            'articles' => [
                [
                    'title' => 'Test Paper',
                    'publication_year' => 2023,
                    'doi' => '10.1234/test',
                    'authors' => [
                        'authors' => [
                            ['full_name' => 'John Smith'],
                            ['full_name' => 'Jane Doe'],
                        ],
                    ],
                ],
            ],
            'total_records' => 1,
        ];
        $response = json_encode($responseData);
        $client = $this->createMockClient([new Response(200, [], $response)]);
        $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);

        $query = new Query(text: 'test');
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Smith', $docs[0]->authors[0]->familyName);
        $this->assertEquals('John', $docs[0]->authors[0]->givenName);
    }
}
