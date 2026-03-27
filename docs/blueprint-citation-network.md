# Blueprint: Citation Network Analysis with PageRank

## Overview

Build citation networks from search/snowball results and use graph algorithms to identify influential papers using PageRank centrality.

## Dependencies

```json
{
    "require": {
        "mbsoft31/graph-core": "^1.0",
        "mbsoft31/graph-algorithms": "^1.0"
    }
}
```

## Architecture

```
src/
├── CitationAnalysis/
│   ├── CitationGraphBuilder.php    # Build graphs from documents
│   ├── NetworkAnalyzer.php        # Graph analysis with algorithms
│   └── Exports/
│       ├── CytoscapeExporter.php  # Cytoscape.js JSON
│       ├── GephiExporter.php       # GEXF format for Gephi
│       └── GraphMLExporter.php     # GraphML format
```

## Core Components

### 1. CitationGraphBuilder

```php
namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class CitationGraphBuilder
{
    public function buildFromDocuments(array $documents): Graph
    {
        // Nodes: papers (identified by DOI or providerId)
        // Edges: citation relationships (A cites B)
    }

    public function buildCitationGraph(
        array $documents,
        array $citedByMap = [] // Optional: citation data from providers
    ): Graph {}

    public function buildCoCitationGraph(array $documents): Graph {}
    
    public function buildBibliographicCouplingGraph(array $documents): Graph {}
}
```

**Key Features:**
- Use DOI as primary node identifier (fallback to providerId)
- Edge weight = number of citations (if available)
- Store document metadata in node attributes
- Support directed (citation) and undirected (similarity) graphs

### 2. NetworkAnalyzer

```php
namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Mbsoft\Graph\Algorithms\Centrality\PageRank;
use Mbsoft\Graph\Algorithms\Traversal\Bfs;
use Mbsoft\Graph\Algorithms\Components\StronglyConnected;

class NetworkAnalyzer
{
    public function __construct(Graph $graph) {}
    
    /**
     * Find most influential papers using PageRank
     * @return array [document_id => score]
     */
    public function findInfluentialPapers(int $topN = 10): array {}
    
    /**
     * Get citation centrality scores
     */
    public function getDegreeCentrality(): array {}
    
    /**
     * Find strongly connected components (clusters of mutually-citing papers)
     */
    public function findClusters(): array {}
    
    /**
     * Find k-core (dense citation subgraphs)
     */
    public function findKCore(int $k): Graph {}
    
    /**
     * Traverse citation network
     */
    public function traverseCitations(string $seedId, int $depth): array {}
    
    /**
     * Find citation path between two papers
     */
    public function findCitationPath(string $fromId, string $toId): ?array {}
}
```

### 3. Exporters

#### CytoscapeExporter
```php
namespace Nexus\CitationAnalysis\Exports;

use Mbsoft\Graph\Domain\Graph;

class CytoscapeExporter
{
    public function export(Graph $graph, array $documents): array
    {
        // Returns Cytoscape.js JSON format
        // Nodes: papers with title, year, citations as data
        // Edges: citation links with weights
    }
}
```

#### GephiExporter  
```php
namespace Nexus\CitationAnalysis\Exports;

use Mbsoft\Graph\Domain\Graph;

class GephiExporter
{
    public function export(Graph $graph, array $documents): string
    {
        // Returns GEXF format for Gephi
        // Includes node attributes: title, year, citations, authors
        // Includes edge attributes: weight, type
    }
}
```

## Usage Examples

### Basic Usage
```php
use Nexus\CitationAnalysis\CitationGraphBuilder;
use Nexus\CitationAnalysis\NetworkAnalyzer;
use Nexus\CitationAnalysis\Exports\GephiExporter;

// 1. Load documents
$documents = loadFromJson('deduped_results.json');

// 2. Build citation graph
$builder = new CitationGraphBuilder();
$graph = $builder->buildFromDocuments($documents);

// 3. Analyze
$analyzer = new NetworkAnalyzer($graph);
$influential = $analyzer->findInfluentialPapers(20);

// 4. Export for visualization
$exporter = new GephiExporter();
$gexf = $exporter->export($graph, $documents);
file_put_contents('citation_network.gexf', $gexf);
```

### Integration with Search
```php
// After running search and snowball
$allDocuments = array_merge($searchResults, $snowballResults);

$builder = new CitationGraphBuilder();
$graph = $builder->buildFromDocuments($allDocuments);

// Find most important papers in your collection
$topPapers = $analyzer->findInfluentialPapers(10);

// Find research clusters
$clusters = $analyzer->findClusters();
```

## API Reference

### CitationGraphBuilder

| Method | Description |
|--------|-------------|
| `buildFromDocuments(array $docs)` | Build citation graph from document list |
| `buildCoCitationGraph(array $docs)` | Build co-citation similarity graph |
| `buildBibliographicCouplingGraph(array $docs)` | Build bibliographic coupling graph |

### NetworkAnalyzer

| Method | Description | Returns |
|--------|-------------|---------|
| `findInfluentialPapers(int $n)` | Top N by PageRank | `array[id => score]` |
| `getDegreeCentrality()` | Citation count centrality | `array[id => count]` |
| `findClusters()` | Strongly connected components | `array[componentId => [nodes]]` |
| `findKCore(int $k)` | K-core subgraph | `Graph` |
| `traverseCitations(string $id, int $depth)` | BFS traversal | `array[nodeIds]` |

### Exporters

| Exporter | Format | Tools |
|----------|--------|-------|
| `CytoscapeExporter` | JSON | Cytoscape.js |
| `GephiExporter` | GEXF | Gephi |
| `GraphMLExporter` | GraphML | yEd, NetworkX |

## Implementation Steps

1. **Add dependencies** to `composer.json`
2. **Create CitationGraphBuilder** - convert documents to graph
3. **Create NetworkAnalyzer** - wrap graph algorithms
4. **Create exporters** - integrate with graph-core exporters
5. **Add tests** - unit + integration
6. **Create demo** - usage example with TomatoMAP data

## Edge Cases

- Documents without DOIs: use providerId as fallback
- Missing citation data: build similarity graphs instead
- Disconnected graphs: handle components separately
- Large graphs: add pagination/batching for >1000 docs
