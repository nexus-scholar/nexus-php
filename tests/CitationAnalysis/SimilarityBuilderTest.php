<?php

namespace Nexus\Tests\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\CitationAnalysis\SimilarityBuilder;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class SimilarityBuilderTest extends TestCase
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

    public function test_build_similarity_network(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);

        $builder = new SimilarityBuilder([$docA, $docB]);
        $graph = $builder->buildSimilarityNetwork();

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertFalse($graph->isDirected());
        $this->assertCount(2, $graph->nodes());
    }

    public function test_get_similarity_matrix(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);

        $builder = new SimilarityBuilder([$docA, $docB]);
        $matrix = $builder->getSimilarityMatrix();

        $this->assertIsArray($matrix);
        $this->assertArrayHasKey('doi:10.1234/a', $matrix);
    }

    public function test_build_combined_graph_with_weights(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2'],
            'referenced_works' => ['ref1', 'ref2'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2'],
            'referenced_works' => ['ref1', 'ref2'],
        ]);

        $builder = new SimilarityBuilder([$docA, $docB]);
        $graph = $builder->buildCombinedGraph(
            cocitationWeight: 0.5,
            couplingWeight: 0.5,
            threshold: 0.1
        );

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertFalse($graph->isDirected());
    }

    public function test_combined_graph_respects_threshold(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite2'],
        ]);

        $builder = new SimilarityBuilder([$docA, $docB]);
        $graph = $builder->buildCombinedGraph(
            cocitationWeight: 0.5,
            couplingWeight: 0.5,
            threshold: 0.5
        );

        $this->assertCount(2, $graph->nodes());
    }

    public function test_empty_documents(): void
    {
        $builder = new SimilarityBuilder([]);
        $graph = $builder->buildSimilarityNetwork();

        $this->assertCount(0, $graph->nodes());
    }

    public function test_weighted_combined_graph(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2'],
            'referenced_works' => ['ref1'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2'],
            'referenced_works' => ['ref1', 'ref2'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'citing_papers' => ['cite3'],
            'referenced_works' => ['ref3'],
        ]);

        $builder = new SimilarityBuilder([$docA, $docB, $docC]);

        $graphHighCoCitation = $builder->buildCombinedGraph(
            cocitationWeight: 1.0,
            couplingWeight: 0.0,
            threshold: 0.1
        );

        $this->assertTrue($graphHighCoCitation->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
    }
}
