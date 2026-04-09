<?php

namespace Nexus\Tests\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Visualization\GraphExporter;
use PHPUnit\Framework\TestCase;

class GraphExporterTest extends TestCase
{
    public function test_export_gexf_format(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'Node A']);

        $result = GraphExporter::export($graph, 'gexf');

        $this->assertIsString($result);
        $this->assertStringContainsString('<gexf', $result);
    }

    public function test_export_graphml_format(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'Node A']);

        $result = GraphExporter::export($graph, 'graphml');

        $this->assertIsString($result);
        $this->assertStringContainsString('<graphml', $result);
    }

    public function test_export_cytoscape_format(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'Node A']);

        $result = GraphExporter::export($graph, 'cytoscape');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('elements', $result);
    }

    public function test_export_unknown_format_throws_exception(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown format: unknown');

        GraphExporter::export($graph, 'unknown');
    }

    public function test_get_supported_formats(): void
    {
        $formats = GraphExporter::getSupportedFormats();

        $this->assertIsArray($formats);
        $this->assertArrayHasKey('gexf', $formats);
        $this->assertArrayHasKey('graphml', $formats);
        $this->assertArrayHasKey('cytoscape', $formats);
        $this->assertEquals('GEXF (Gephi)', $formats['gexf']);
        $this->assertEquals('GraphML (yEd, NetworkX)', $formats['graphml']);
        $this->assertEquals('Cytoscape.js JSON', $formats['cytoscape']);
    }

    public function test_format_is_case_insensitive(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'A']);

        $resultUpper = GraphExporter::export($graph, 'GEXF');
        $resultLower = GraphExporter::export($graph, 'gexf');

        $this->assertStringContainsString('<gexf', $resultUpper);
        $this->assertStringContainsString('<gexf', $resultLower);
    }

    public function test_save_gexf_to_file(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'A']);
        $graph->addNode('b', ['label' => 'B']);
        $graph->addEdge('a', 'b');

        $tempFile = sys_get_temp_dir().'/test_export_'.uniqid().'.gexf';

        GraphExporter::save($graph, 'gexf', $tempFile);

        $this->assertFileExists($tempFile);

        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('<gexf', $content);

        unlink($tempFile);
    }

    public function test_save_creates_directory(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'A']);

        $tempDir = sys_get_temp_dir().'/nexus_test_'.uniqid();
        $tempFile = $tempDir.'/subdir/test.graphml';

        GraphExporter::save($graph, 'graphml', $tempFile);

        $this->assertFileExists($tempFile);

        unlink($tempFile);
        rmdir(dirname($tempFile));
        rmdir($tempDir);
    }

    public function test_save_cytoscape_as_json(): void
    {
        $graph = new Graph(directed: true);
        $graph->addNode('a', ['label' => 'A']);

        $tempFile = sys_get_temp_dir().'/test_export_'.uniqid().'.json';

        GraphExporter::save($graph, 'cytoscape', $tempFile);

        $this->assertFileExists($tempFile);

        $content = file_get_contents($tempFile);
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('elements', $decoded);

        unlink($tempFile);
    }
}
