# Blueprint: Co-citation and Bibliographic Coupling Analysis

## Overview

Find related papers through co-citation analysis and bibliographic coupling - two proven methods for literature mapping that don't require citation data from providers.

## Concepts

### Co-citation
- Papers A and B are **co-cited** if they're cited together by a third paper C
- High co-citation = papers are semantically related
- Good for: Finding papers on similar topics

### Bibliographic Coupling  
- Papers A and B are **bibliographically coupled** if they share a common reference
- Coupling strength = number of shared references
- Good for: Finding papers with similar methodology

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
│   ├── SimilarityBuilder.php       # Build similarity graphs
│   ├── CoCitationAnalyzer.php     # Co-citation analysis
│   └── BibliographicCoupling.php  # Bibliographic coupling
```

## CoCitationAnalyzer

```php
namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class CoCitationAnalyzer
{
    public function __construct(array $documents) {}
    
    /**
     * Build co-citation matrix from documents
     * @return array [paperIdA][paperIdB] = co-citation count
     */
    public function buildCoCitationMatrix(): array;
    
    /**
     * Get all papers that cite both A and B
     * @return array [paperId => [citingPapers]]
     */
    public function getCoCitingPapers(string $paperA, string $paperB): array;
    
    /**
     * Find most similar papers to a given paper
     * @return array [paperId => similarityScore]
     */
    public function findSimilarPapers(string $paperId, int $topN = 10): array;
    
    /**
     * Build similarity graph based on co-citation
     * @return Graph Undirected graph with edge weights = co-citation count
     */
    public function buildSimilarityGraph(float $minThreshold = 0.1): Graph;
    
    /**
     * Get normalized similarity (Jaccard-like)
     * @return array [paperIdA][paperIdB] = normalized score
     */
    public function getNormalizedSimilarity(): array;
}
```

### Algorithm Details

```
Co-citation strength(A, B) = |citing_papers(A) ∩ citing_papers(B)|

Normalized(A, B) = strength(A, B) / |citing_papers(A) ∪ citing_papers(B)|
```

## BibliographicCoupling

```php
namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class BibliographicCoupling
{
    public function __construct(array $documents) {}
    
    /**
     * Build coupling matrix from documents
     * @return array [paperIdA][paperIdB] = shared reference count
     */
    public function buildCouplingMatrix(): array;
    
    /**
     * Find papers with strongest coupling to a given paper
     * @return array [paperId => couplingStrength]
     */
    public function findCoupledPapers(string $paperId, int $topN = 10): array;
    
    /**
     * Build coupling similarity graph
     * @return Graph Undirected graph with edge weights
     */
    public function buildCouplingGraph(float $minThreshold = 1): Graph;
    
    /**
     * Find clusters based on bibliographic coupling
     * @return array [clusterId => [paperIds]]
     */
    public function findCouplingClusters(int $minCoupling = 2): array;
}
```

### Algorithm Details

```
Bibliographic coupling(A, B) = |references(A) ∩ references(B)|

Note: Requires reference lists from OpenAlex/semantic scholar
```

## SimilarityBuilder (Facade)

```php
namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;

class SimilarityBuilder
{
    public function __construct(array $documents) {}
    
    /**
     * Build combined similarity graph
     * Uses both co-citation and bibliographic coupling
     */
    public function buildCombinedGraph(
        float $cocitationWeight = 0.5,
        float $couplingWeight = 0.5,
        float $threshold = 0.1
    ): Graph {}
    
    /**
     * Build undirected similarity network from documents
     */
    public function buildSimilarityNetwork(): Graph {}
    
    /**
     * Get similarity matrix
     */
    public function getSimilarityMatrix(): array {}
}
```

## Usage Examples

### Co-citation Analysis
```php
use Nexus\CitationAnalysis\CoCitationAnalyzer;

// Load documents (from search/snowball)
$documents = loadFromJson('deduped_results.json');

$analyzer = new CoCitationAnalyzer($documents);

// Find papers similar to a seed paper
$similar = $analyzer->findSimilarPapers($seedDoi, topN: 15);

echo "Papers similar to: $seedTitle\n";
foreach ($similar as $paperId => $score) {
    $doc = findDocument($paperId);
    echo "  - {$doc->title} (score: {$score})\n";
}

// Build similarity graph
$similarityGraph = $analyzer->buildSimilarityGraph(threshold: 0.2);
```

### Bibliographic Coupling
```php
use Nexus\CitationAnalysis\BibliographicCoupling;

$coupling = new BibliographicCoupling($documents);

// Find papers with similar references
$similarRefs = $coupling->findCoupledPapers($seedDoi, topN: 10);

// Find research clusters
$clusters = $coupling->findCouplingClusters(minCoupling: 2);

echo "Found " . count($clusters) . " clusters\n";
foreach ($clusters as $i => $cluster) {
    echo "Cluster " . ($i+1) . ": " . count($cluster) . " papers\n";
}
```

### Combined Similarity
```php
use Nexus\CitationAnalysis\SimilarityBuilder;
use Nexus\Visualization\GraphExporter;

$builder = new SimilarityBuilder($documents);

// Build combined similarity network
$network = $builder->buildCombinedGraph(
    cocitationWeight: 0.6,
    couplingWeight: 0.4,
    threshold: 0.15
);

// Export to Gephi for visualization
GraphExporter::save($network, 'gexf', 'similarity_network.gexf', $documents);
```

### Integration with NetworkAnalyzer
```php
use Nexus\CitationAnalysis\SimilarityBuilder;
use Nexus\CitationAnalysis\NetworkAnalyzer;

$builder = new SimilarityBuilder($documents);
$graph = $builder->buildSimilarityNetwork();

// Use PageRank to find influential papers in similarity network
$analyzer = new NetworkAnalyzer($graph);
$centralPapers = $analyzer->findInfluentialPapers(20);
```

## API Reference

### CoCitationAnalyzer

| Method | Description | Returns |
|--------|-------------|---------|
| `buildCoCitationMatrix()` | Build co-citation counts | `array[a][b] = count` |
| `findSimilarPapers(id, n)` | Top N similar | `array[id => score]` |
| `buildSimilarityGraph(threshold)` | Similarity network | `Graph` |
| `getNormalizedSimilarity()` | Jaccard-normalized | `array[a][b] = score` |

### BibliographicCoupling

| Method | Description | Returns |
|--------|-------------|---------|
| `buildCouplingMatrix()` | Shared reference counts | `array[a][b] = count` |
| `findCoupledPapers(id, n)` | Top N coupled | `array[id => count]` |
| `buildCouplingGraph(threshold)` | Coupling network | `Graph` |
| `findCouplingClusters(n)` | Clusters | `array[clusterId => [ids]]` |

### SimilarityBuilder

| Method | Description |
|--------|-------------|
| `buildCombinedGraph(w1, w2, t)` | Combined similarity |
| `buildSimilarityNetwork()` | Undireted similarity graph |
| `getSimilarityMatrix()` | Full similarity matrix |

## Data Requirements

### Co-citation
- Requires: List of papers that cite each document
- Source: OpenAlex `cited_by_count` + citation list API
- Fallback: Use document collection as implicit co-citation

### Bibliographic Coupling
- Requires: Reference list for each document
- Source: OpenAlex `referenced_works`
- Note: Only available if papers have reference data

## Implementation Steps

1. **Create CoCitationAnalyzer** - co-citation logic
2. **Create BibliographicCoupling** - coupling logic
3. **Create SimilarityBuilder** - unified builder
4. **Integrate with exporters** - GEXF/GraphML output
5. **Add tests** - unit tests for similarity calculations
6. **Create demo** - analyze TomatoMAP papers

## Advantages for SLR

1. **No external API calls** - Uses document collection data
2. **Finds related papers** - Beyond simple keyword matching
3. **Identifies clusters** - Natural research groups
4. **Complements snowballing** - Alternative discovery method

## Edge Cases

- Single document: Cannot compute similarity (need 2+ docs)
- No references: Bibliographic coupling won't work
- Small collections: Limited co-citation pairs
- Threshold tuning: Adjust for graph density
