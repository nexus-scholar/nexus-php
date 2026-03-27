# Blueprint: Visualization Export (GEXF/GraphML for Gephi)

## Overview

Export citation and similarity networks to standard graph formats for visualization in tools like Gephi, Cytoscape.js, and yEd.

## Dependencies

```json
{
    "require": {
        "mbsoft31/graph-core": "^1.0"
    }
}
```

## Architecture

```
src/
├── Visualization/
│   ├── GraphExporter.php           # Main exporter facade
│   ├── GexfExporter.php           # GEXF (Gephi)
│   ├── GraphMLExporter.php        # GraphML (yEd, NetworkX)
│   └── CytoscapeExporter.php     # Cytoscape.js JSON
```

## GexfExporter

```php
namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;

class GexfExporter
{
    public function __construct(
        ?string $version = '1.3',
        ?string $creator = 'nexus-php'
    ) {}
    
    public function export(
        Graph $graph,
        ?array $documents = [],
        ?array $options = []
    ): string {}
    
    // Options:
    // - include_attributes: bool
    // - edge_weight_attribute: string
    // - node_color_attribute: string
}
```

### Node Attributes
| Attribute | Type | Description |
|-----------|------|-------------|
| `id` | string | Unique node ID (DOI or providerId) |
| `label` | string | Display label (paper title) |
| `title` | string | Full paper title |
| `year` | int | Publication year |
| `citations` | int | Citation count |
| `doi` | string | DOI |
| `venue` | string | Publication venue |
| `query_id` | string | Source query |

### Edge Attributes
| Attribute | Type | Description |
|-----------|------|-------------|
| `source` | string | Source node ID |
| `target` | string | Target node ID |
| `weight` | float | Edge weight (citation count) |
| `type` | string | "directed" or "undirected" |

## GraphMLExporter

```php
namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;

class GraphMLExporter
{
    public function __construct(?string $creator = 'nexus-php') {}
    
    public function export(
        Graph $graph,
        ?array $documents = [],
        ?array $options = []
    ): string {}
}
```

### Features
- Full XML schema support
- Node/edge color attributes
- Custom attribute definitions
- Compatible with yEd, NetworkX, Gephi

## CytoscapeExporter

```php
namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;

class CytoscapeExporter
{
    public function export(
        Graph $graph,
        ?array $documents = [],
        ?array $options = []
    ): array {}
    
    // Returns Cytoscape.js JSON format:
    // [
    //     'elements' => [
    //         'nodes' => [...],
    //         'edges' => [...]
    //     ],
    //     'data' => [...],
    //     'layout' => [...]
    // ]
}
```

## GraphExporter (Facade)

```php
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
        return match ($format) {
            'gexf' => (new GexfExporter())->export($graph, $documents, $options),
            'graphml' => (new GraphMLExporter())->export($graph, $documents, $options),
            'cytoscape' => (new CytoscapeExporter())->export($graph, $documents, $options),
            default => throw new \InvalidArgumentException("Unknown format: $format")
        };
    }
    
    public static function save(
        Graph $graph,
        string $format,
        string $filepath,
        ?array $documents = []
    ): void {}
}
```

## Usage Examples

### Basic Export
```php
use Nexus\Visualization\GraphExporter;
use Nexus\CitationAnalysis\CitationGraphBuilder;

// Build graph from documents
$builder = new CitationGraphBuilder();
$graph = $builder->buildFromDocuments($documents);

// Export to GEXF (Gephi)
$gexf = GraphExporter::export($graph, 'gexf', $documents);
file_put_contents('network.gexf', $gexf);

// Export to GraphML (yEd)
$graphml = GraphExporter::export($graph, 'graphml', $documents);
file_put_contents('network.graphml', $graphml);

// Export to Cytoscape.js
$cytoscape = GraphExporter::export($graph, 'cytoscape', $documents);
file_put_contents('network.json', json_encode($cytoscape));
```

### With Custom Options
```php
$options = [
    'include_attributes' => true,
    'edge_weight_attribute' => 'citations',
    'node_color_attribute' => 'year',
];

$gexf = GraphExporter::export($graph, 'gexf', $documents, $options);
```

### Save Directly to File
```php
GraphExporter::save($graph, 'gexf', 'output/citations.gexf', $documents);
```

## Export Formats Comparison

| Format | Extension | Tools | Best For |
|--------|-----------|-------|----------|
| GEXF | .gexf | Gephi | Network analysis, clustering |
| GraphML | .graphml | yEd, NetworkX | Multi-graph, attributes |
| Cytoscape | .json | Cytoscape.js | Web visualization |

## Gephi Workflow

1. Export from nexus-php: `GraphExporter::save($graph, 'gexf', 'network.gexf')`
2. Open in Gephi: File → Open → network.gexf
3. Visualize:
   - Layout: ForceAtlas2 or Fruchterman-Reingold
   - Color: By year or cluster
   - Size: By citation count or PageRank
4. Export: PNG, SVG, or PDF

## Implementation Steps

1. **Create GexfExporter** - GEXF format
2. **Create GraphMLExporter** - GraphML format  
3. **Create CytoscapeExporter** - Cytoscape.js JSON
4. **Create GraphExporter facade** - unified API
5. **Add tests** - verify output format
6. **Create demo** - export TomatoMAP network

## Edge Cases

- Empty graphs: Return valid empty XML/JSON
- Missing document metadata: Use only IDs
- Large graphs (>10k nodes): Add streaming export option
- Special characters in titles: XML escape properly
- Unicode: UTF-8 encoding for all formats
