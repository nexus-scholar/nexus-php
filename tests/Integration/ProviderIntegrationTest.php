<?php

namespace Nexus\Tests\Integration;

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Models\ProviderConfig;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\CrossrefProvider;
use Nexus\Providers\ArxivProvider;
use Nexus\Providers\SemanticScholarProvider;
use Nexus\Providers\PubMedProvider;
use Nexus\Providers\IEEEProvider;
use Nexus\Providers\DOAJProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ProviderIntegrationTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    public function test_openalex_complete_flow(): void
    {
        $responseData = [
            'results' => [
                [
                    'id' => 'https://openalex.org/W2893546371',
                    'doi' => 'https://doi.org/10.1234/test',
                    'title' => 'Machine Learning for Climate Prediction',
                    'publication_year' => 2023,
                    'authorships' => [
                        [
                            'author' => ['display_name' => 'John Smith'],
                            'author_position' => 'first',
                        ],
                        [
                            'author' => ['display_name' => 'Jane Doe'],
                            'author_position' => 'last',
                        ],
                    ],
                    'primary_location' => [
                        'source' => ['display_name' => 'Nature Climate Change'],
                    ],
                    'abstract_inverted_index' => null,
                    'cited_by_count' => 150,
                ],
            ],
            'meta' => ['count' => 1],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData)),
        ]);

        $config = new ProviderConfig(name: 'openalex');
        $provider = new OpenAlexProvider($config, $client);

        $query = new Query(text: 'machine learning climate', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Machine Learning for Climate Prediction', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals(150, $docs[0]->citedByCount);
        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Nature Climate Change', $docs[0]->venue);
        $this->assertEquals('10.1234/test', $docs[0]->externalIds->doi);
    }

    public function test_crossref_complete_flow(): void
    {
        $responseData = [
            'message' => [
                'items' => [
                    [
                        'DOI' => '10.1234/crossref-test',
                        'title' => ['Deep Learning in Healthcare'],
                        'author' => [
                            ['given' => 'Alice', 'family' => 'Johnson'],
                            ['given' => 'Bob', 'family' => 'Williams'],
                        ],
                        'issued' => ['date-parts' => [[2022, 5, 15]]],
                        'container-title' => ['Journal of Medical AI'],
                        'abstract' => 'This paper explores...',
                    ],
                ],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData)),
        ]);

        $config = new ProviderConfig(name: 'crossref');
        $provider = new CrossrefProvider($config, $client);

        $query = new Query(text: 'deep learning healthcare', maxResults: 5);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Deep Learning in Healthcare', $docs[0]->title);
        $this->assertEquals(2022, $docs[0]->year);
        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Johnson', $docs[0]->authors[0]->familyName);
        $this->assertEquals('Journal of Medical AI', $docs[0]->venue);
    }

    public function test_arxiv_complete_flow(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <title>Quantum Computing Algorithms</title>
                <summary>We present novel quantum algorithms for optimization problems.</summary>
                <published>2023-03-10T00:00:00Z</published>
                <id>https://arxiv.org/abs/2303.12345v1</id>
                <arxiv:doi xmlns:arxiv="http://arxiv.org/schemas/atom">10.48550/arXiv.2303.12345</arxiv:doi>
                <author><name>Quantum Researcher</name></author>
                <arxiv:primary_category xmlns:arxiv="http://arxiv.org/schemas/atom" term="quant-ph"/>
            </entry>
        </feed>';

        $client = $this->createMockClient([
            new Response(200, [], $xml),
        ]);

        $config = new ProviderConfig(name: 'arxiv');
        $provider = new ArxivProvider($config, $client);

        $query = new Query(text: 'quantum computing', maxResults: 5);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Quantum Computing Algorithms', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals('2303.12345', $docs[0]->externalIds->arxivId);
        $this->assertStringContainsString('quant-ph', $docs[0]->venue);
    }

    public function test_semantic_scholar_complete_flow(): void
    {
        $responseData = [
            'total' => 1,
            'token' => null,
            'data' => [
                [
                    'paperId' => 'S2-abc123',
                    'title' => 'Neural Network Architectures',
                    'abstract' => 'A comprehensive survey of neural network architectures.',
                    'year' => 2023,
                    'venue' => 'IEEE Transactions',
                    'url' => 'https://www.semanticscholar.org/paper/abc123',
                    'citationCount' => 500,
                    'externalIds' => [
                        'DOI' => '10.1109/TNNLS.2023.123456',
                        'ArXiv' => '2301.12345',
                    ],
                    'authors' => [
                        ['authorId' => 'A1', 'name' => 'Alice Chen'],
                        ['authorId' => 'A2', 'name' => 'Bob Liu'],
                    ],
                ],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData)),
        ]);

        $config = new ProviderConfig(name: 's2');
        $provider = new SemanticScholarProvider($config, $client);

        $query = new Query(text: 'neural network', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Neural Network Architectures', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals(500, $docs[0]->citedByCount);
        $this->assertEquals('10.1109/tnnls.2023.123456', $docs[0]->externalIds->doi);
        $this->assertEquals('2301.12345', $docs[0]->externalIds->arxivId);
        $this->assertCount(2, $docs[0]->authors);
        $this->assertEquals('Chen', $docs[0]->authors[0]->familyName);
    }

    public function test_pubmed_complete_flow(): void
    {
        $searchResponse = '<?xml version="1.0" encoding="UTF-8"?>
        <eSearchResult>
            <Count>1</Count>
            <IdList>
                <Id>12345678</Id>
            </IdList>
            <WebEnv>test_webenv</WebEnv>
            <QueryKey>1</QueryKey>
        </eSearchResult>';

        $fetchResponse = '<?xml version="1.0" encoding="UTF-8"?>
        <PubmedArticleSet>
            <PubmedArticle>
                <MedlineCitation>
                    <PMID>12345678</PMID>
                    <Article>
                        <ArticleTitle>Gene Therapy Advances</ArticleTitle>
                        <Abstract>
                            <AbstractText>This study presents advances in gene therapy.</AbstractText>
                        </Abstract>
                        <AuthorList>
                            <Author>
                                <LastName>Bio</LastName>
                                <ForeName>Alice</ForeName>
                            </Author>
                        </AuthorList>
                        <Journal>
                            <Title>Nature Biotechnology</Title>
                            <JournalIssue>
                                <PubDate>
                                    <Year>2023</Year>
                                </PubDate>
                            </JournalIssue>
                        </Journal>
                    </Article>
                </MedlineCitation>
                <PubmedData>
                    <ArticleIdList>
                        <ArticleId IdType="doi">10.1038/nbt.2023.001</ArticleId>
                    </ArticleIdList>
                </PubmedData>
            </PubmedArticle>
        </PubmedArticleSet>';

        $client = $this->createMockClient([
            new Response(200, [], $searchResponse),
            new Response(200, [], $fetchResponse),
        ]);

        $config = new ProviderConfig(name: 'pubmed');
        $provider = new PubMedProvider($config, $client);

        $query = new Query(text: 'gene therapy', maxResults: 10);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Gene Therapy Advances', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals('12345678', $docs[0]->externalIds->pubmedId);
        $this->assertEquals('10.1038/nbt.2023.001', $docs[0]->externalIds->doi);
        $this->assertEquals('Nature Biotechnology', $docs[0]->venue);
        $this->assertStringContainsString('pubmed.ncbi.nlm.nih.gov', $docs[0]->url);
    }

    public function test_ieee_complete_flow(): void
    {
        $responseData = [
            'total_records' => 2,
            'articles' => [
                [
                    'title' => '5G Network Optimization',
                    'publication_year' => 2023,
                    'doi' => '10.1109/ICC.2023.12345',
                    'article_number' => '12345678',
                    'html_url' => 'https://ieeexplore.ieee.org/document/12345678',
                    'abstract' => 'This paper presents 5G network optimization techniques.',
                    'publication_title' => 'IEEE Communications',
                    'authors' => [
                        'authors' => [
                            ['full_name' => 'Network Expert'],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData)),
        ]);

        $config = new ProviderConfig(name: 'ieee', apiKey: 'test_key');
        $provider = new IEEEProvider($config, $client);

        $query = new Query(text: '5G networks', maxResults: 5);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('5G Network Optimization', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals('10.1109/icc.2023.12345', $docs[0]->externalIds->doi);
        $this->assertEquals('IEEE Communications', $docs[0]->venue);
    }

    public function test_doaj_complete_flow(): void
    {
        $responseData = [
            'total' => 1,
            'results' => [
                [
                    'id' => 'doaj_article_123',
                    'bibjson' => [
                        'title' => 'Open Access Research Methods',
                        'year' => '2023',
                        'abstract' => 'This article presents open access research methodologies.',
                        'author' => [
                            ['name' => 'Open Researcher'],
                        ],
                        'journal' => [
                            'title' => 'Journal of OA Studies',
                        ],
                        'identifier' => [
                            ['type' => 'doi', 'id' => '10.1234/oa.2023.001'],
                            ['type' => 'url', 'id' => 'https://example.com/article'],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData)),
        ]);

        $config = new ProviderConfig(name: 'doaj');
        $provider = new DOAJProvider($config, $client);

        $query = new Query(text: 'research methods open access', maxResults: 5);
        $docs = iterator_to_array($provider->search($query));

        $this->assertCount(1, $docs);
        $this->assertInstanceOf(Document::class, $docs[0]);
        $this->assertEquals('Open Access Research Methods', $docs[0]->title);
        $this->assertEquals(2023, $docs[0]->year);
        $this->assertEquals('10.1234/oa.2023.001', $docs[0]->externalIds->doi);
        $this->assertEquals('Journal of OA Studies', $docs[0]->venue);
        $this->assertCount(1, $docs[0]->authors);
        $this->assertEquals('Open Researcher', $docs[0]->authors[0]->getFullName());
    }

    public function test_all_providers_handle_empty_results(): void
    {
        $providers = [
            ['name' => 'openalex', 'class' => OpenAlexProvider::class, 'response' => json_encode(['results' => [], 'meta' => ['count' => 0]])],
            ['name' => 'crossref', 'class' => CrossrefProvider::class, 'response' => json_encode(['message' => ['items' => []]])],
            ['name' => 'arxiv', 'class' => ArxivProvider::class, 'response' => '<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom"></feed>'],
            ['name' => 's2', 'class' => SemanticScholarProvider::class, 'response' => json_encode(['total' => 0, 'data' => []])],
            ['name' => 'doaj', 'class' => DOAJProvider::class, 'response' => json_encode(['results' => [], 'total' => 0])],
        ];

        foreach ($providers as $providerInfo) {
            $client = $this->createMockClient([
                new Response(200, [], $providerInfo['response']),
            ]);

            $config = new ProviderConfig(name: $providerInfo['name']);
            $provider = new $providerInfo['class']($config, $client);

            $query = new Query(text: 'nonexistent_query_xyz123');
            $docs = iterator_to_array($provider->search($query));

            $this->assertEmpty($docs, "Provider {$providerInfo['name']} should return empty results");
        }
    }
}
