<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\DOAJProvider;

function createDOAJMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}
it('returns correct provider name', function () {
    $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'));
    expect($provider->getName())->toBe('doaj');
});
it('handles empty results', function () {
    $response = json_encode(['results' => []]);
    $client = createDOAJMockClient([new Response(200, [], $response)]);
    $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);
    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toBeEmpty();
});
it('returns documents', function () {
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
    $client = createDOAJMockClient([new Response(200, [], $response)]);
    $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);
    $query = new Query(text: 'test');
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toHaveCount(1);
    expect($docs[0]->title)->toBe('Test Paper');
    expect($docs[0]->year)->toBe(2023);
    expect($docs[0]->externalIds->doi)->toBe('10.1234/test');
});
it('extracts authors', function () {
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
    $client = createDOAJMockClient([new Response(200, [], $response)]);
    $provider = new DOAJProvider(new ProviderConfig(name: 'doaj'), $client);
    $query = new Query(text: 'test');
    $docs = iterator_to_array($provider->search($query));
    expect($docs[0]->authors)->toHaveCount(2);
    expect($docs[0]->authors[0]->familyName)->toBe('Smith');
});
