# Visualization Module

The Visualization module exports graph structures into formats that external tools can render and analyze.

## Files

- `src/Visualization/GraphExporter.php`
- `src/Visualization/GexfExporter.php`
- `src/Visualization/GraphMLExporter.php`
- `src/Visualization/CytoscapeExporter.php`

## Supported targets

- **GEXF** for Gephi.
- **GraphML** for graph tooling such as yEd and Python ecosystems.
- **Cytoscape.js JSON** for web-based graph rendering.

## Example usage

```php
use Nexus\Visualization\GexfExporter;

$xml = (new GexfExporter())->export($graph);
file_put_contents('citation-network.gexf', $xml);
```

```php
use Nexus\Visualization\GraphMLExporter;

file_put_contents('citation-network.graphml', (new GraphMLExporter())->export($graph));
```

## Typical workflow

1. Build a citation or similarity graph.
2. Analyze or filter the graph.
3. Export in the format required by your tooling.
4. Open in Gephi, Cytoscape.js, NetworkX, or another graph environment.

## Best practices

- Deduplicate before exporting graphs.
- Enrich nodes with metadata such as title, year, provider, and DOI.
- Keep a JSON export for reproducibility alongside graph formats.
