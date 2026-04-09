<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\OpenAlexProvider;

function createMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function createMockResponse(array $data): Response
{
    return new Response(200, [], json_encode($data));
}

it('returns correct provider name', function () {
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'));
    expect($provider->getName())->toBe('openalex');
});

it('returns documents from api', function () {
    $mockResponse = createMockResponse([
        'results' => [
            [
                'display_name' => 'Test Paper',
                'publication_year' => 2024,
                'id' => 'https://openalex.org/W1234567',
                'doi' => 'https://doi.org/10.1234/test',
                'cited_by_count' => 50,
                'authorships' => [],
            ],
        ],
        'meta' => ['next_cursor' => null],
    ]);

    $client = createMockClient([$mockResponse]);
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex', mailto: 'test@example.com'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(1);
    expect($docs[0])->toBeInstanceOf(Document::class);
    expect($docs[0]->title)->toBe('Test Paper');
    expect($docs[0]->year)->toBe(2024);
});

it('handles empty results', function () {
    $mockResponse = createMockResponse([
        'results' => [],
        'meta' => ['next_cursor' => null],
    ]);

    $client = createMockClient([$mockResponse]);
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'), $client);

    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('respects max results', function () {
    $results = [];
    for ($i = 0; $i < 5; $i++) {
        $results[] = [
            'display_name' => "Paper $i",
            'publication_year' => 2024,
            'id' => "https://openalex.org/W$i",
            'doi' => "https://doi.org/10.1234/test$i",
            'cited_by_count' => $i,
            'authorships' => [],
        ];
    }

    $mockResponse = createMockResponse([
        'results' => $results,
        'meta' => ['next_cursor' => 'next'],
    ]);

    $client = createMockClient([$mockResponse]);
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'), $client);

    $query = new Query(text: 'test', maxResults: 2);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(2);
});

it('handles missing title', function () {
    $mockResponse = createMockResponse([
        'results' => [
            [
                'id' => 'https://openalex.org/W1234567',
            ],
        ],
        'meta' => ['next_cursor' => null],
    ]);

    $client = createMockClient([$mockResponse]);
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('deduplicates by provider id', function () {
    $mockResponse = createMockResponse([
        'results' => [
            [
                'display_name' => 'Paper 1',
                'publication_year' => 2024,
                'id' => 'https://openalex.org/W1234567',
                'doi' => 'https://doi.org/10.1234/test',
                'cited_by_count' => 50,
                'authorships' => [],
            ],
            [
                'display_name' => 'Paper 2',
                'publication_year' => 2024,
                'id' => 'https://openalex.org/W1234567',
                'doi' => 'https://doi.org/10.1234/test',
                'cited_by_count' => 50,
                'authorships' => [],
            ],
        ],
        'meta' => ['next_cursor' => null],
    ]);

    $client = createMockClient([$mockResponse]);
    $provider = new OpenAlexProvider(new ProviderConfig(name: 'openalex'), $client);

    $query = new Query(text: 'test', maxResults: 10);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(1);
});
