<?php

namespace Nexus\Tests\Providers;

use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\CrossrefProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CrossrefProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    private function createMockResponse(array $items, ?string $nextCursor = null): Response
    {
        return new Response(200, [], json_encode([
            'message' => [
                'items' => $items,
                'next-cursor' => $nextCursor,
            ],
        ]));
    }

    public function test_provider_returns_correct_name()
    {
        $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'));
        $this->assertEquals('crossref', $provider->getName());
    }

    public function test_search_returns_documents()
    {
        $mockResponse = $this->createMockResponse([
            [
                'title' => ['Test Paper Title'],
                'DOI' => '10.1234/test',
                'issued' => ['date-parts' => [[2024, 1, 15]]],
                'is-referenced-by-count' => 25,
                'author' => [],
                'abstract' => 'Test abstract',
                'container-title' => ['Nature'],
                'URL' => 'https://example.com/article',
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref', mailto: 'test@example.com'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Test Paper Title', $docs[0]->title);
        $this->assertEquals(2024, $docs[0]->year);
        $this->assertEquals('10.1234/test', $docs[0]->externalIds->doi);
    }

    public function test_search_handles_empty_results()
    {
        $mockResponse = $this->createMockResponse([]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref'),
            $client
        );

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_handles_missing_title()
    {
        $mockResponse = $this->createMockResponse([
            ['DOI' => '10.1234/test'],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_deduplicates_by_doi()
    {
        $mockResponse = $this->createMockResponse([
            [
                'title' => ['Paper 1'],
                'DOI' => '10.1234/same',
                'issued' => ['date-parts' => [[2024]]],
            ],
            [
                'title' => ['Paper 2'],
                'DOI' => '10.1234/same',
                'issued' => ['date-parts' => [[2024]]],
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
    }

    public function test_search_extracts_authors()
    {
        $mockResponse = $this->createMockResponse([
            [
                'title' => ['Test Paper'],
                'DOI' => '10.1234/test',
                'issued' => ['date-parts' => [[2024]]],
                'author' => [
                    ['family' => 'Smith', 'given' => 'John'],
                    ['family' => 'Doe', 'given' => 'Jane'],
                ],
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Smith', $docs[0]->authors[0]->familyName);
    }

    public function test_search_extracts_venue()
    {
        $mockResponse = $this->createMockResponse([
            [
                'title' => ['Test Paper'],
                'DOI' => '10.1234/test',
                'issued' => ['date-parts' => [[2024]]],
                'container-title' => ['Nature', 'Science'],
                'URL' => 'https://example.com',
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new CrossrefProvider(
            new ProviderConfig(name: 'crossref'),
            $client
        );

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEquals('Nature', $docs[0]->venue);
    }
}
