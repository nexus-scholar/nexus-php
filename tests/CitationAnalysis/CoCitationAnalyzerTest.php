<?php

namespace Nexus\Tests\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\CitationAnalysis\CoCitationAnalyzer;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class CoCitationAnalyzerTest extends TestCase
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

    public function test_build_co_citation_matrix(): void
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

        $analyzer = new CoCitationAnalyzer([$docA, $docB, $docC]);
        $matrix = $analyzer->buildCoCitationMatrix();

        $this->assertEquals(2, $matrix['doi:10.1234/a']['doi:10.1234/b']);
        $this->assertArrayNotHasKey('doi:10.1234/a', $matrix['doi:10.1234/c']);
    }

    public function test_get_co_citing_papers(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite3'],
        ]);

        $analyzer = new CoCitationAnalyzer([$docA, $docB]);
        $cociting = $analyzer->getCoCitingPapers('doi:10.1234/a', 'doi:10.1234/b');

        $this->assertArrayHasKey('cite1', $cociting);
        $this->assertArrayNotHasKey('cite2', $cociting);
        $this->assertArrayNotHasKey('cite3', $cociting);
    }

    public function test_find_similar_papers(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2', 'cite3', 'cite4'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'citing_papers' => ['cite1'],
        ]);

        $analyzer = new CoCitationAnalyzer([$docA, $docB, $docC]);
        $similar = $analyzer->findSimilarPapers('doi:10.1234/a', 2);

        $this->assertCount(2, $similar);
        $this->assertArrayHasKey('doi:10.1234/b', $similar);
        $this->assertGreaterThan($similar['doi:10.1234/c'], $similar['doi:10.1234/b']);
    }

    public function test_find_similar_papers_not_found(): void
    {
        $doc = $this->createDocument('Paper A', '10.1234/a');

        $analyzer = new CoCitationAnalyzer([$doc]);
        $similar = $analyzer->findSimilarPapers('doi:10.1234/a');

        $this->assertEmpty($similar);
    }

    public function test_build_similarity_graph(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'citing_papers' => ['cite5'],
        ]);

        $analyzer = new CoCitationAnalyzer([$docA, $docB, $docC]);
        $graph = $analyzer->buildSimilarityGraph(0.1);

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertFalse($graph->isDirected());
        $this->assertTrue($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
        $this->assertFalse($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/c'));
    }

    public function test_get_normalized_similarity(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);

        $analyzer = new CoCitationAnalyzer([$docA, $docB]);
        $normalized = $analyzer->getNormalizedSimilarity();

        $this->assertEquals(1.0, $normalized['doi:10.1234/a']['doi:10.1234/b']);
    }

    public function test_normalized_similarity_jaccard(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'citing_papers' => ['cite1', 'cite2', 'cite3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'citing_papers' => ['cite1', 'cite2'],
        ]);

        $analyzer = new CoCitationAnalyzer([$docA, $docB]);
        $normalized = $analyzer->getNormalizedSimilarity();

        $expectedJaccard = 2.0 / 3.0;
        $this->assertEqualsWithDelta($expectedJaccard, $normalized['doi:10.1234/a']['doi:10.1234/b'], 0.001);
    }

    public function test_empty_documents(): void
    {
        $analyzer = new CoCitationAnalyzer([]);
        $matrix = $analyzer->buildCoCitationMatrix();

        $this->assertEmpty($matrix);
    }
}
