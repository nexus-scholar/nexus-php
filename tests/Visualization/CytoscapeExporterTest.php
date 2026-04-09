<?php

namespace Nexus\Tests\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Visualization\CytoscapeExporter;
use PHPUnit\Framework\TestCase;

class CytoscapeExporterTest extends TestCase
{
    public function test_export_empty_graph(): void
    {
        $graph = new Graph(directed: true);
        $exporter = new CytoscapeExporter;

        $result = $exporter->export($graph);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('elements', $result);
        $this->assertArrayHasKey('nodes', $result['elements']);
        $this->assertArrayHasKey('edges', $result['elements']);
        $this->assertEmpty($result['elements']['nodes']);
        $this->assertEmpty($result['elements']['edges']);
    }

    public function test_export_graph_with_nodes(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('paper_a', [
            'label' => 'Paper A',
            'title' => 'Paper A Title',
            'year' => 2024,
            'citations' => 100,
        ]);
        $graph->addNode('paper_b', [
            'label' => 'Paper B',
            'title' => 'Paper B Title',
        ]);

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertCount(2, $result['elements']['nodes']);

        $nodeIds = array_column($result['elements']['nodes'], 'data');
        $nodeIds = array_column($nodeIds, 'id');
        $this->assertContains('paper_a', $nodeIds);
        $this->assertContains('paper_b', $nodeIds);
    }

    public function test_export_directed_graph_has_citation_edges(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 2.5]);

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertCount(1, $result['elements']['edges']);
        $this->assertEquals('cites', $result['elements']['edges'][0]['data']['interaction']);
        $this->assertEquals(2.5, $result['elements']['edges'][0]['data']['weight']);
    }

    public function test_export_undirected_graph_has_similarity_edges(): void
    {
        $graph = new Graph(directed: false);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 1.5]);

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertCount(1, $result['elements']['edges']);
        $this->assertEquals('similar_to', $result['elements']['edges'][0]['data']['interaction']);
    }

    public function test_export_includes_metadata(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('nexus-php', $result['data']['creator']);
    }

    public function test_export_includes_schema(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertArrayHasKey('data_schema', $result);
        $this->assertArrayHasKey('nodes', $result['data_schema']);
        $this->assertArrayHasKey('edges', $result['data_schema']);
    }

    public function test_export_includes_year_color(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'A', 'year' => 2024]);

        $exporter = new CytoscapeExporter;
        $result = $exporter->export($graph);

        $this->assertArrayHasKey('color', $result['elements']['nodes'][0]['data']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $result['elements']['nodes'][0]['data']['color']);
    }
}
