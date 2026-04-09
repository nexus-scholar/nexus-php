<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\ArxivProvider;

function createArxivMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function createArxivMockXmlResponse(string $entries): Response
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
        <entry>'.$entries.'</entry>
    </feed>';

    return new Response(200, [], $xml);
}

it('returns correct provider name', function () {
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'));
    expect($provider->getName())->toBe('arxiv');
});

it('returns documents from api', function () {
    $xml = '
        <title>Test Paper Title</title>
        <summary>Abstract text here.</summary>
        <published>2024-01-15T00:00:00Z</published>
        <id>https://arxiv.org/abs/2301.12345v1</id>
        <arxiv:doi xmlns:arxiv="http://arxiv.org/schemas/atom">10.1234/test</arxiv:doi>
        <author><name>John Smith</name></author>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toHaveCount(1);
    expect($docs[0])->toBeInstanceOf(Document::class);
    expect(trim($docs[0]->title))->toBe('Test Paper Title');
});

it('handles empty results', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom"></feed>';

    $client = createArxivMockClient([new Response(200, [], $xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'nonexistent');
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('extracts arxiv id', function () {
    $xml = '
        <title>Test</title>
        <published>2024-01-01T00:00:00Z</published>
        <id>https://arxiv.org/abs/2301.12345v2</id>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs[0]->externalIds->arxivId)->toBe('2301.12345');
});

it('extracts year', function () {
    $xml = '
        <title>Test</title>
        <published>2023-06-20T00:00:00Z</published>
        <id>https://arxiv.org/abs/2301.12345</id>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs[0]->year)->toBe(2023);
});

it('filters by year min', function () {
    $xml = '
        <title>Test 2022</title>
        <published>2022-01-01T00:00:00Z</published>
        <id>https://arxiv.org/abs/2201.12345</id>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1, yearMin: 2023);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('filters by year max', function () {
    $xml = '
        <title>Test 2025</title>
        <published>2025-01-01T00:00:00Z</published>
        <id>https://arxiv.org/abs/2501.12345</id>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1, yearMax: 2024);
    $docs = iterator_to_array($provider->search($query));

    expect($docs)->toBeEmpty();
});

it('extracts doi', function () {
    $xml = '
        <title>Test</title>
        <published>2024-01-01T00:00:00Z</published>
        <id>https://arxiv.org/abs/2301.12345</id>
        <arxiv:doi xmlns:arxiv="http://arxiv.org/schemas/atom">10.1234/test</arxiv:doi>
    ';

    $client = createArxivMockClient([createArxivMockXmlResponse($xml)]);
    $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

    $query = new Query(text: 'test', maxResults: 1);
    $docs = iterator_to_array($provider->search($query));

    expect($docs[0]->externalIds->doi)->toBe('10.1234/test');
});
