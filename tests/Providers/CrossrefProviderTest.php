<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\CrossrefProvider;

function createCrossrefMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}
function createCrossrefMockResponse(array $items, ?string $nextCursor = null): Response
{
    return new Response(200, [], json_encode([
        'message' => [
            'items' => $items,
            'next-cursor' => $nextCursor,
        ],
    ]));
}
it('returns correct provider name', function () {
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'));
    expect($provider->getName())->toBe('crossref');
});
it('returns documents from api', function () {
    $mockResponse = createCrossrefMockResponse([
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
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref', mailto: 'test@example.com'), $client);
    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toHaveCount(1);
    expect($docs[0])->toBeInstanceOf(Document::class);
    expect($docs[0]->title)->toBe('Test Paper Title');
    expect($docs[0]->year)->toBe(2024);
    expect($docs[0]->externalIds->doi)->toBe('10.1234/test');
});
it('handles empty results', function () {
    $mockResponse = createCrossrefMockResponse([]);
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'), $client);
    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toBeEmpty();
});
it('handles missing title', function () {
    $mockResponse = createCrossrefMockResponse([
        ['DOI' => '10.1234/test'],
    ]);
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'), $client);
    $query = new Query(text: 'test', maxResults: 10);
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toBeEmpty();
});
it('deduplicates by doi', function () {
    $mockResponse = createCrossrefMockResponse([
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
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'), $client);
    $query = new Query(text: 'test', maxResults: 10);
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toHaveCount(1);
});
it('extracts authors', function () {
    $mockResponse = createCrossrefMockResponse([
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
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'), $client);
    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toHaveCount(1);
    expect($docs[0]->authors)->toHaveCount(2);
    expect($docs[0]->authors[0]->familyName)->toBe('Smith');
});
it('extracts venue', function () {
    $mockResponse = createCrossrefMockResponse([
        [
            'title' => ['Test Paper'],
            'DOI' => '10.1234/test',
            'issued' => ['date-parts' => [[2024]]],
            'container-title' => ['Nature', 'Science'],
            'URL' => 'https://example.com',
        ],
    ]);
    $client = createCrossrefMockClient([$mockResponse]);
    $provider = new CrossrefProvider(new ProviderConfig(name: 'crossref'), $client);
    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));
    expect($docs[0]->venue)->toBe('Nature');
});
