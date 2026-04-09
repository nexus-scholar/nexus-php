<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\IEEEProvider;
use Nexus\Utils\Exceptions\AuthenticationError;

function createIEEEMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}
it('returns correct provider name', function () {
    $provider = new IEEEProvider(new ProviderConfig(name: 'ieee'));
    expect($provider->getName())->toBe('ieee');
});
it('requires api key', function () {
    $provider = new IEEEProvider(new ProviderConfig(name: 'ieee'));
    $query = new Query(text: 'test');
    iterator_to_array($provider->search($query));
})->throws(AuthenticationError::class);
it('handles empty results', function () {
    $response = json_encode(['articles' => []]);
    $client = createIEEEMockClient([new Response(200, [], $response)]);
    $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);
    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toBeEmpty();
});
it('returns documents', function () {
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
    $client = createIEEEMockClient([new Response(200, [], $response)]);
    $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);
    $query = new Query(text: 'test');
    $docs = iterator_to_array($provider->search($query));
    expect($docs)->toHaveCount(1);
    expect($docs[0]->title)->toBe('Test Paper');
    expect($docs[0]->year)->toBe(2023);
    expect($docs[0]->externalIds->doi)->toBe('10.1234/test');
});
it('extracts authors', function () {
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
    $client = createIEEEMockClient([new Response(200, [], $response)]);
    $provider = new IEEEProvider(new ProviderConfig(name: 'ieee', apiKey: 'test_key'), $client);
    $query = new Query(text: 'test');
    $docs = iterator_to_array($provider->search($query));
    expect($docs[0]->authors)->toHaveCount(2);
    expect($docs[0]->authors[0]->familyName)->toBe('Smith');
    expect($docs[0]->authors[0]->givenName)->toBe('John');
});
