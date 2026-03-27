<?php

namespace Nexus\Tests\Providers;

use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\SemanticScholarProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SemanticScholarProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    private function createMockResponse(array $data, ?string $token = null): Response
    {
        return new Response(200, [], json_encode([
            'data' => $data,
            'token' => $token,
        ]));
    }

    public function test_provider_returns_correct_name()
    {
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'));
        $this->assertEquals('s2', $provider->getName());
    }

    public function test_search_returns_documents()
    {
        $mockResponse = $this->createMockResponse([
            [
                'paperId' => 'paper-123',
                'title' => 'Test Paper Title',
                'year' => 2024,
                'abstract' => 'This is the abstract.',
                'venue' => 'ICML',
                'citationCount' => 100,
                'authors' => [],
                'externalIds' => [],
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Test Paper Title', $docs[0]->title);
        $this->assertEquals(2024, $docs[0]->year);
        $this->assertEquals('This is the abstract.', $docs[0]->abstract);
        $this->assertEquals('ICML', $docs[0]->venue);
        $this->assertEquals(100, $docs[0]->citedByCount);
    }

    public function test_search_handles_empty_results()
    {
        $mockResponse = $this->createMockResponse([]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_handles_missing_title()
    {
        $mockResponse = $this->createMockResponse([
            ['paperId' => 'paper-123'],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'test', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_extracts_authors()
    {
        $mockResponse = $this->createMockResponse([
            [
                'paperId' => 'paper-123',
                'title' => 'Test',
                'year' => 2024,
                'authors' => [
                    ['name' => 'John Smith'],
                    ['name' => 'Jane Doe'],
                ],
                'externalIds' => [],
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Smith', $docs[0]->authors[0]->familyName);
        $this->assertEquals('John', $docs[0]->authors[0]->givenName);
    }

    public function test_search_extracts_external_ids()
    {
        $mockResponse = $this->createMockResponse([
            [
                'paperId' => 'paper-123',
                'title' => 'Test',
                'year' => 2024,
                'authors' => [],
                'externalIds' => [
                    'DOI' => '10.1234/test',
                    'ArXiv' => '2301.12345',
                    'PubMed' => '12345678',
                ],
            ],
        ]);

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEquals('10.1234/test', $docs[0]->externalIds->doi);
        $this->assertEquals('2301.12345', $docs[0]->externalIds->arxivId);
        $this->assertEquals('12345678', $docs[0]->externalIds->pubmedId);
        $this->assertEquals('paper-123', $docs[0]->externalIds->s2Id);
    }

    public function test_search_respects_max_results()
    {
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = [
                'paperId' => "paper-$i",
                'title' => "Paper $i",
                'year' => 2024,
                'authors' => [],
                'externalIds' => [],
            ];
        }

        $mockResponse = $this->createMockResponse($results, 'next-token');

        $client = $this->createMockClient([$mockResponse]);
        $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

        $query = new Query(text: 'test', maxResults: 2);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(2, $docs);
    }
}
