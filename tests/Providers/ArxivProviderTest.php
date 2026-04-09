<?php

namespace Nexus\Tests\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Nexus\Providers\ArxivProvider;
use PHPUnit\Framework\TestCase;

class ArxivProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    private function createMockXmlResponse(string $entries): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <entry>'.$entries.'</entry>
        </feed>';

        return new Response(200, [], $xml);
    }

    public function test_provider_returns_correct_name()
    {
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'));
        $this->assertEquals('arxiv', $provider->getName());
    }

    public function test_search_returns_documents()
    {
        $xml = '
            <title>Test Paper Title</title>
            <summary>Abstract text here.</summary>
            <published>2024-01-15T00:00:00Z</published>
            <id>https://arxiv.org/abs/2301.12345v1</id>
            <arxiv:doi xmlns:arxiv="http://arxiv.org/schemas/atom">10.1234/test</arxiv:doi>
            <author><name>John Smith</name></author>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Test Paper Title', trim($docs[0]->title));
    }

    public function test_search_handles_empty_results()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom"></feed>';

        $client = $this->createMockClient([new Response(200, [], $xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'nonexistent');
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_extracts_arxiv_id()
    {
        $xml = '
            <title>Test</title>
            <published>2024-01-01T00:00:00Z</published>
            <id>https://arxiv.org/abs/2301.12345v2</id>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEquals('2301.12345', $docs[0]->externalIds->arxivId);
    }

    public function test_search_extracts_year()
    {
        $xml = '
            <title>Test</title>
            <published>2023-06-20T00:00:00Z</published>
            <id>https://arxiv.org/abs/2301.12345</id>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEquals(2023, $docs[0]->year);
    }

    public function test_search_filters_by_year_min()
    {
        $xml = '
            <title>Test 2022</title>
            <published>2022-01-01T00:00:00Z</published>
            <id>https://arxiv.org/abs/2201.12345</id>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1, yearMin: 2023);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_filters_by_year_max()
    {
        $xml = '
            <title>Test 2025</title>
            <published>2025-01-01T00:00:00Z</published>
            <id>https://arxiv.org/abs/2501.12345</id>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1, yearMax: 2024);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEmpty($docs);
    }

    public function test_search_extracts_doi()
    {
        $xml = '
            <title>Test</title>
            <published>2024-01-01T00:00:00Z</published>
            <id>https://arxiv.org/abs/2301.12345</id>
            <arxiv:doi xmlns:arxiv="http://arxiv.org/schemas/atom">10.1234/test</arxiv:doi>
        ';

        $client = $this->createMockClient([$this->createMockXmlResponse($xml)]);
        $provider = new ArxivProvider(new ProviderConfig(name: 'arxiv'), $client);

        $query = new Query(text: 'test', maxResults: 1);
        $docs = iterator_to_array($provider->search($query));

        $this->assertEquals('10.1234/test', $docs[0]->externalIds->doi);
    }
}
