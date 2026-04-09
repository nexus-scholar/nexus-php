<?php

namespace Nexus\Tests\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\CitationAnalysis\CitationGraphBuilder;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class CitationGraphBuilderTest extends TestCase
{
    private function createDocument(
        string $title,
        ?string $doi = null,
        array $rawData = []
    ): Document {
        return new Document(
            title: $title,
            year: 2024,
            provider: 'openalex',
            providerId: uniqid('doc_'),
            externalIds: new ExternalIds(doi: $doi),
            rawData: $rawData
        );
    }

    public function test_build_from_documents_creates_graph(): void
    {
        $documents = [
            $this->createDocument('Paper A', '10.1234/a'),
            $this->createDocument('Paper B', '10.1234/b'),
            $this->createDocument('Paper C', '10.1234/c'),
        ];

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildFromDocuments($documents);

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertCount(3, $graph->nodes());
        $this->assertTrue($graph->isDirected());
    }

    public function test_build_citation_graph_adds_references(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a');
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['doi:10.1234/a'],
        ]);

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildCitationGraph([$docA, $docB]);

        $this->assertCount(2, $graph->nodes());
        $this->assertTrue($graph->hasEdge('doi:10.1234/b', 'doi:10.1234/a'));
    }

    public function test_build_co_citation_graph(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2', 'cite4'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'citing_papers' => ['cite5', 'cite6'],
        ]);

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildCoCitationGraph([$docA, $docB, $docC]);

        $this->assertCount(3, $graph->nodes());
        $this->assertFalse($graph->isDirected());
        $this->assertTrue($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
        $this->assertFalse($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/c'));
    }

    public function test_build_bibliographic_coupling_graph(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1', 'ref2', 'ref3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref1', 'ref2', 'ref4'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'referenced_works' => ['ref5', 'ref6'],
        ]);

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildBibliographicCouplingGraph([$docA, $docB, $docC]);

        $this->assertCount(3, $graph->nodes());
        $this->assertFalse($graph->isDirected());
        $this->assertTrue($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
        $this->assertFalse($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/c'));
    }

    public function test_node_attributes_include_metadata(): void
    {
        $doc = $this->createDocument('Test Paper', '10.1234/test', [
            'year' => 2023,
        ]);

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildFromDocuments([$doc]);

        $attrs = $graph->nodeAttrs('doi:10.1234/test');

        $this->assertEquals('doi:10.1234/test', $attrs['id']);
        $this->assertEquals('Test Paper', $attrs['title']);
        $this->assertEquals('Test Paper', $attrs['label']);
    }

    public function test_empty_documents_returns_empty_graph(): void
    {
        $builder = new CitationGraphBuilder;
        $graph = $builder->buildFromDocuments([]);

        $this->assertCount(0, $graph->nodes());
        $this->assertCount(0, $graph->edges());
    }

    public function test_document_without_doi_uses_provider_id(): void
    {
        $doc = new Document(
            title: 'No DOI Paper',
            year: 2024,
            provider: 'openalex',
            providerId: 'W123456789'
        );

        $builder = new CitationGraphBuilder;
        $graph = $builder->buildFromDocuments([$doc]);

        $this->assertCount(1, $graph->nodes());
        $this->assertTrue($graph->hasNode('id:W123456789'));
    }
}
