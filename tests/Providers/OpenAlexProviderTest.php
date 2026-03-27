<?php

namespace Nexus\Tests\Providers;

use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\OpenAlexProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class OpenAlexProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    private function createMockResponse(array $data): Response
    {
        return new Response(200, [], json_encode($data));
    }

    public function test_provider_returns_correct_name()
    {
        $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'));
        $this->assertEquals('openalex', $provider->getName());
    }

    public function test_search_returns_documents()
    {
        $mockResponse = $this->createMockResponse([
            'results' => [
                [
                    'display_name' => 'Test Paper',
                    'title' => 'Test Paper',
                    'publication_year' => 2024,
                    'id' => 'https://openalex.org/W1234567',
                    'doi' => 'https://doi.org/10.1234/test',
                    'cited_by_count' => 50,
                    'authorships' => [],
                    'abstract_inverted_index' => null,
                ],
            ],
            'meta' => ['next_cursor' => null],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAlexProvider(
            new ProviderConfig(name: 'openalex', mailto: 'test@example.com'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Test Paper', $docs[0]->title);
        $this->assertEquals(2024, $docs[0]->year);
    }

    public function test_search_handles_empty_results()
    {
        $mockResponse = $this->createMockResponse([
            'results' => [],
            'meta' => ['next_cursor' => null],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAlexProvider(
            new ProviderConfig(name: 'openalex'),
            $client
        );

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_respects_max_results()
    {
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = [
                'display_name' => "Paper $i",
                'title' => "Paper $i",
                'publication_year' => 2024,
                'id' => "https://openalex.org/W$i",
                'doi' => "https://doi.org/10.1234/test$i",
                'cited_by_count' => $i,
                'authorships' => [],
            ];
        }

        $mockResponse = $this->createMockResponse([
            'results' => $results,
            'meta' => ['next_cursor' => 'next'],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAlexProvider(
            new ProviderConfig(name: 'openalex'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 2);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(2, $docs);
    }

    public function test_search_handles_missing_title()
    {
        $mockResponse = $this->createMockResponse([
            'results' => [
                [
                    'id' => 'https://openalex.org/W1234567',
                ],
            ],
            'meta' => ['next_cursor' => null],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAlexProvider(
            new ProviderConfig(name: 'openalex'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_deduplicates_by_provider_id()
    {
        $mockResponse = $this->createMockResponse([
            'results' => [
                [
                    'display_name' => 'Paper 1',
                    'title' => 'Paper 1',
                    'publication_year' => 2024,
                    'id' => 'https://openalex.org/W1234567',
                    'doi' => 'https://doi.org/10.1234/test',
                    'cited_by_count' => 50,
                    'authorships' => [],
                ],
                [
                    'display_name' => 'Paper 2',
                    'title' => 'Paper 2',
                    'publication_year' => 2024,
                    'id' => 'https://openalex.org/W1234567',
                    'doi' => 'https://doi.org/10.1234/test',
                    'cited_by_count' => 50,
                    'authorships' => [],
                ],
            ],
            'meta' => ['next_cursor' => null],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAlexProvider(
            new ProviderConfig(name: 'openalex'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
    }
}
