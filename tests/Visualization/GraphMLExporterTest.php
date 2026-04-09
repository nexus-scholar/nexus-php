<?php

namespace Nexus\Tests\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Visualization\GraphMLExporter;
use PHPUnit\Framework\TestCase;

class GraphMLExporterTest extends TestCase
{
    public function test_export_empty_graph(): void
    {
        $graph = new Graph(directed: true);
        $exporter = new GraphMLExporter;

        $graphml = $exporter->export($graph);

        $this->assertStringContainsString('<?xml', $graphml);
        $this->assertStringContainsString('<graphml', $graphml);
        $this->assertStringContainsString('<graph', $graphml);
    }

    public function test_export_graph_with_nodes(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('paper_a', [
            'label' => 'Paper A',
            'title' => 'Paper A Title',
            'year' => 2024,
        ]);
        $graph->addNode('paper_b', [
            'label' => 'Paper B',
            'title' => 'Paper B Title',
        ]);

        $exporter = new GraphMLExporter;
        $graphml = $exporter->export($graph);

        $this->assertStringContainsString('id="paper_a"', $graphml);
        $this->assertStringContainsString('id="paper_b"', $graphml);
    }

    public function test_export_directed_graph(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 2.5]);

        $exporter = new GraphMLExporter;
        $graphml = $exporter->export($graph);

        $this->assertStringContainsString('edgedefault="directed"', $graphml);
        $this->assertStringContainsString('>2.5<', $graphml);
    }

    public function test_export_undirected_graph(): void
    {
        $graph = new Graph(directed: false);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 1.5]);

        $exporter = new GraphMLExporter;
        $graphml = $exporter->export($graph);

        $this->assertStringContainsString('edgedefault="undirected"', $graphml);
    }

    public function test_export_includes_key_definitions(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'Node A']);

        $exporter = new GraphMLExporter;
        $graphml = $exporter->export($graph);

        $this->assertStringContainsString('<key', $graphml);
        $this->assertStringContainsString('attr.name="label"', $graphml);
    }
}
