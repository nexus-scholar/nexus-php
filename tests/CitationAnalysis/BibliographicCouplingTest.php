<?php

namespace Nexus\Tests\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\CitationAnalysis\BibliographicCoupling;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class BibliographicCouplingTest extends TestCase
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

    public function test_build_coupling_matrix(): void
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

        $coupling = new BibliographicCoupling([$docA, $docB, $docC]);
        $matrix = $coupling->buildCouplingMatrix();

        $this->assertEquals(2, $matrix['doi:10.1234/a']['doi:10.1234/b']);
        $this->assertArrayNotHasKey('doi:10.1234/a', $matrix['doi:10.1234/c']);
    }

    public function test_find_coupled_papers(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1', 'ref2', 'ref3', 'ref4'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref1', 'ref2', 'ref3'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'referenced_works' => ['ref1'],
        ]);

        $coupling = new BibliographicCoupling([$docA, $docB, $docC]);
        $coupled = $coupling->findCoupledPapers('doi:10.1234/a', 3);

        $this->assertCount(3, $coupled);
        $this->assertArrayHasKey('doi:10.1234/b', $coupled);
        $this->assertArrayHasKey('doi:10.1234/c', $coupled);
        $this->assertEquals(3, $coupled['doi:10.1234/b']);
        $this->assertEquals(1, $coupled['doi:10.1234/c']);
    }

    public function test_find_coupled_papers_not_found(): void
    {
        $doc = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref2', 'ref3'],
        ]);

        $coupling = new BibliographicCoupling([$doc, $docB]);
        $coupled = $coupling->findCoupledPapers('doi:10.1234/a');

        $this->assertArrayNotHasKey('doi:10.1234/b', $coupled);
    }

    public function test_build_coupling_graph(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1', 'ref2', 'ref3'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref1', 'ref2', 'ref3'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'referenced_works' => ['ref5'],
        ]);

        $coupling = new BibliographicCoupling([$docA, $docB, $docC]);
        $graph = $coupling->buildCouplingGraph(1.0);

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertFalse($graph->isDirected());
        $this->assertTrue($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
        $this->assertFalse($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/c'));
    }

    public function test_find_coupling_clusters(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1', 'ref2'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref1', 'ref2'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'referenced_works' => ['ref1', 'ref2'],
        ]);
        $docD = $this->createDocument('Paper D', '10.1234/d', [
            'referenced_works' => ['ref5'],
        ]);

        $coupling = new BibliographicCoupling([$docA, $docB, $docC, $docD]);
        $clusters = $coupling->findCouplingClusters(1);

        $this->assertNotEmpty($clusters);
        $hasAbcCluster = false;
        foreach ($clusters as $cluster) {
            if (count($cluster) === 3 &&
                in_array('doi:10.1234/a', $cluster) &&
                in_array('doi:10.1234/b', $cluster) &&
                in_array('doi:10.1234/c', $cluster)) {
                $hasAbcCluster = true;
                break;
            }
        }
        $this->assertTrue($hasAbcCluster);
    }

    public function test_empty_documents(): void
    {
        $coupling = new BibliographicCoupling([]);
        $matrix = $coupling->buildCouplingMatrix();

        $this->assertEmpty($matrix);
    }

    public function test_threshold_filters_edges(): void
    {
        $docA = $this->createDocument('Paper A', '10.1234/a', [
            'referenced_works' => ['ref1'],
        ]);
        $docB = $this->createDocument('Paper B', '10.1234/b', [
            'referenced_works' => ['ref1'],
        ]);
        $docC = $this->createDocument('Paper C', '10.1234/c', [
            'referenced_works' => ['ref2'],
        ]);

        $coupling = new BibliographicCoupling([$docA, $docB, $docC]);
        $graph = $coupling->buildCouplingGraph(2.0);

        $this->assertFalse($graph->hasEdge('doi:10.1234/a', 'doi:10.1234/b'));
    }
}
