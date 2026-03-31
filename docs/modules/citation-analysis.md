# Citation Analysis Module

The Citation Analysis module builds graphs from scholarly references and computes network-level metrics that help identify influential works, research clusters, and structural relationships in a corpus.

## Files

- `src/CitationAnalysis/CitationGraphBuilder.php`
- `src/CitationAnalysis/NetworkAnalyzer.php`
- `src/CitationAnalysis/CoCitationAnalyzer.php`
- `src/CitationAnalysis/BibliographicCoupling.php`
- `src/CitationAnalysis/SimilarityBuilder.php`

## What this module enables

- Build directed citation graphs.
- Measure influence with PageRank and centrality.
- Discover co-citation patterns.
- Discover bibliographic coupling.
- Support literature mapping and bibliometric analysis.

## Core workflow

```php
use Nexus\CitationAnalysis\CitationGraphBuilder;
use Nexus\CitationAnalysis\NetworkAnalyzer;

$graph = (new CitationGraphBuilder())->buildCitationGraph($documents);
$analyzer = new NetworkAnalyzer($graph);

$top = $analyzer->findInfluentialPapers(limit: 10);
$pageRank = $analyzer->computePageRank();
$kCore = $analyzer->computeKCore();
```

## Concepts

### Citation graph

A directed graph where one paper cites another paper.

### Co-citation

Two documents are co-cited when a third document cites both of them. This often reveals perceived conceptual relatedness.

### Bibliographic coupling

Two documents are bibliographically coupled when they cite the same references. This often reveals topical similarity among current works.

## Example: identify influential papers

```php
$graph = $builder->buildCitationGraph($documents);
$analyzer = new NetworkAnalyzer($graph);

foreach ($analyzer->findInfluentialPapers(limit: 20) as $paper) {
    // report high-impact papers
}
```

## Example: co-citation map

```php
$coCitation = new CoCitationAnalyzer();
$matrix = $coCitation->analyze($documents);
```

## Use cases

- Finding seminal papers in a domain.
- Building related-work maps.
- Detecting thematic clusters.
- Supporting review articles and research-gap analysis.
- Exporting graphs to external visualization tools.

## Best practices

- Deduplicate before building graphs.
- Use high-quality metadata sources for reference edges.
- Keep provenance between nodes and source documents.
- Export intermediate graph artifacts for reproducibility.
