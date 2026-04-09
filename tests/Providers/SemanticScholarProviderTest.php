<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\SemanticScholarProvider;

function createS2MockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function createS2MockResponse(array $data, ?string $token = null): Response
{
    return new Response(200, [], json_encode([
        'data' => $data,
        'token' => $token,
    ]));
}

it('returns correct provider name', function () {
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'));
    expect($provider->getName())->toBe('s2');
});

it('returns documents from api', function () {
    $mockResponse = createS2MockResponse([
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

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(1);
    expect($docs[0])->toBeInstanceOf(Document::class);
    expect($docs[0]->title)->toBe('Test Paper Title');
    expect($docs[0]->year)->toBe(2024);
    expect($docs[0]->abstract)->toBe('This is the abstract.');
    expect($docs[0]->venue)->toBe('ICML');
    expect($docs[0]->citedByCount)->toBe(100);
});

it('handles empty results', function () {
    $mockResponse = createS2MockResponse([]);

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('handles missing title', function () {
    $mockResponse = createS2MockResponse([
        ['paperId' => 'paper-123'],
    ]);

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'test', maxResults: 10);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('extracts authors', function () {
    $mockResponse = createS2MockResponse([
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

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs[0]->authors)->toHaveCount(2);
    expect($docs[0]->authors[0]->familyName)->toBe('Smith');
    expect($docs[0]->authors[0]->givenName)->toBe('John');
});

it('extracts external ids', function () {
    $mockResponse = createS2MockResponse([
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

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs[0]->externalIds->doi)->toBe('10.1234/test');
    expect($docs[0]->externalIds->arxivId)->toBe('2301.12345');
    expect($docs[0]->externalIds->pubmedId)->toBe('12345678');
    expect($docs[0]->externalIds->s2Id)->toBe('paper-123');
});

it('respects max results', function () {
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

    $mockResponse = createS2MockResponse($results, 'next-token');

    $client = createS2MockClient([$mockResponse]);
    $provider = new SemanticScholarProvider(new ProviderConfig(name: 's2'), $client);

    $query = new Query(text: 'test', maxResults: 2);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(2);
});
