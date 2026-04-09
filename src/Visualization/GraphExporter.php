<?php

namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;

class GraphExporter
{
    public static function export(
        Graph $graph,
        string $format,
        ?array $documents = [],
        ?array $options = []
    ): string|array {
        return match (strtolower($format)) {
            'gexf' => (new GexfExporter)->export($graph, $documents, $options),
            'graphml' => (new GraphMLExporter)->export($graph, $documents, $options),
            'cytoscape' => (new CytoscapeExporter)->export($graph, $documents, $options),
            default => throw new \InvalidArgumentException("Unknown format: $format. Supported formats: gexf, graphml, cytoscape"),
        };
    }

    public static function save(
        Graph $graph,
        string $format,
        string $filepath,
        ?array $documents = [],
        ?array $options = []
    ): void {
        $content = self::export($graph, $format, $documents, $options);

        $directory = dirname($filepath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, recursive: true);
        }

        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($filepath, $content);
    }

    public static function getSupportedFormats(): array
    {
        return [
            'gexf' => 'GEXF (Gephi)',
            'graphml' => 'GraphML (yEd, NetworkX)',
            'cytoscape' => 'Cytoscape.js JSON',
        ];
    }
}
