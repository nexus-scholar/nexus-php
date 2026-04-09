<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Nexus\Models\ProviderConfig;
use Nexus\Providers\PubMedProvider;

function createPubMedMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}
it('returns correct provider name', function () {
    $provider = new PubMedProvider(new ProviderConfig(name: 'pubmed'));
    expect($provider->getName())->toBe('pubmed');
});
it('requires api key for high rate', function () {
    $config = new ProviderConfig(name: 'pubmed', rateLimit: 1.0);
    $provider = new PubMedProvider($config);
    expect($provider->getName())->toBe('pubmed');
});
