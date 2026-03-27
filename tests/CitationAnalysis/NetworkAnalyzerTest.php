<?php

namespace Nexus\Tests\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\CitationAnalysis\NetworkAnalyzer;
use PHPUnit\Framework\TestCase;

class NetworkAnalyzerTest extends TestCase
{
    public function test_find_influential_papers_with_empty_graph(): void
    {
        $graph = new Graph(directed: true);
        $analyzer = new NetworkAnalyzer($graph);

        $influential = $analyzer->findInfluentialPapers(10);

        $this->assertEmpty($influential);
    }

    public function test_find_influential_papers_with_citations(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('paper_a', ['label' => 'Paper A']);
        $graph->addNode('paper_b', ['label' => 'Paper B']);
        $graph->addNode('paper_c', ['label' => 'Paper C']);
        $graph->addNode('paper_d', ['label' => 'Paper D']);

        $graph->addEdge('paper_a', 'paper_d');
        $graph->addEdge('paper_b', 'paper_d');
        $graph->addEdge('paper_c', 'paper_d');

        $analyzer = new NetworkAnalyzer($graph);
        $influential = $analyzer->findInfluentialPapers(2);

        $this->assertCount(2, $influential);
        $this->assertArrayHasKey('paper_d', $influential);
    }

    public function test_get_degree_centrality(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'c');
        $graph->addEdge('a', 'c');

        $analyzer = new NetworkAnalyzer($graph);
        $centrality = $analyzer->getDegreeCentrality();

        $this->assertArrayHasKey('a', $centrality);
        $this->assertArrayHasKey('b', $centrality);
        $this->assertArrayHasKey('c', $centrality);
        $this->assertEquals(2, $centrality['a']['out_degree']);
        $this->assertEquals(1, $centrality['b']['in_degree']);
    }

    public function test_find_clusters_undirected_graph(): void
    {
        $graph = new Graph(directed: false);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addNode('d');
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'c');

        $analyzer = new NetworkAnalyzer($graph);
        $clusters = $analyzer->findClusters();

        $this->assertNotEmpty($clusters);
    }

    public function test_find_clusters_directed_graph(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'a');
        $graph->addEdge('b', 'c');

        $analyzer = new NetworkAnalyzer($graph);
        $clusters = $analyzer->findClusters();

        $this->assertNotEmpty($clusters);
        $hasAbCluster = false;
        foreach ($clusters as $cluster) {
            if (in_array('a', $cluster) && in_array('b', $cluster)) {
                $hasAbCluster = true;
                break;
            }
        }
        $this->assertTrue($hasAbCluster);
    }

    public function test_traverse_citations(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('seed');
        $graph->addNode('level1_a');
        $graph->addNode('level1_b');
        $graph->addNode('level2');
        $graph->addEdge('seed', 'level1_a');
        $graph->addEdge('seed', 'level1_b');
        $graph->addEdge('level1_a', 'level2');

        $analyzer = new NetworkAnalyzer($graph);
        $visited = $analyzer->traverseCitations('seed', 2);

        $this->assertContains('level1_a', $visited);
        $this->assertContains('level1_b', $visited);
        $this->assertContains('level2', $visited);
        $this->assertNotContains('seed', $visited);
    }

    public function test_find_citation_path_exists(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'c');

        $analyzer = new NetworkAnalyzer($graph);
        $path = $analyzer->findCitationPath('a', 'c');

        $this->assertNotNull($path);
        $this->assertEquals(['a', 'b', 'c'], $path);
    }

    public function test_find_citation_path_not_exists(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addEdge('a', 'b');

        $analyzer = new NetworkAnalyzer($graph);
        $path = $analyzer->findCitationPath('b', 'a');

        $this->assertNull($path);
    }

    public function test_find_k_core(): void
    {
        $graph = new Graph(directed: false);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addNode('c');
        $graph->addNode('d');
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'c');
        $graph->addEdge('c', 'a');
        $graph->addEdge('a', 'd');

        $analyzer = new NetworkAnalyzer($graph);
        $kCore = $analyzer->findKCore(2);

        $this->assertTrue($kCore->hasNode('a'));
        $this->assertTrue($kCore->hasNode('b'));
        $this->assertTrue($kCore->hasNode('c'));
    }
}
