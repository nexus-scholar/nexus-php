<?php

namespace Nexus\Tests\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Visualization\GexfExporter;
use PHPUnit\Framework\TestCase;

class GexfExporterTest extends TestCase
{
    public function test_export_empty_graph(): void
    {
        $graph = new Graph(directed: true);
        $exporter = new GexfExporter;

        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('<?xml', $gexf);
        $this->assertStringContainsString('<gexf', $gexf);
        $this->assertStringContainsString('<nodes', $gexf);
        $this->assertStringContainsString('<edges', $gexf);
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
            'year' => 2023,
        ]);

        $exporter = new GexfExporter;
        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('id="paper_a"', $gexf);
        $this->assertStringContainsString('id="paper_b"', $gexf);
        $this->assertStringContainsString('label="Paper A"', $gexf);
    }

    public function test_export_directed_graph(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 2.5]);

        $exporter = new GexfExporter;
        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('defaultedgetype="directed"', $gexf);
        $this->assertStringContainsString('weight="2.5"', $gexf);
    }

    public function test_export_undirected_graph(): void
    {
        $graph = new Graph(directed: false);
        $graph->addNode('a');
        $graph->addNode('b');
        $graph->addEdge('a', 'b', ['weight' => 1.5]);

        $exporter = new GexfExporter;
        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('defaultedgetype="undirected"', $gexf);
    }

    public function test_export_with_options(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'Node A', 'year' => 2024]);
        $graph->addNode('b', ['label' => 'Node B', 'year' => 2020]);

        $exporter = new GexfExporter(version: '1.2');
        $gexf = $exporter->export($graph, [], ['include_attributes' => false]);

        $this->assertStringContainsString('version="1.2"', $gexf);
    }

    public function test_export_includes_metadata(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');

        $exporter = new GexfExporter(creator: 'test-creator');
        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('<creator>test-creator</creator>', $gexf);
    }

    public function test_xml_special_characters_escaped(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', [
            'label' => 'Paper with <special> & "chars"',
            'title' => 'Title with <special> chars',
        ]);

        $exporter = new GexfExporter;
        $gexf = $exporter->export($graph);

        $this->assertStringContainsString('&lt;special&gt;', $gexf);
        $this->assertStringNotContainsString('<special>', $gexf);
    }
}
