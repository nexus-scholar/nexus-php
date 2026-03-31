# Usage Patterns

This guide collects common end-to-end workflows you can build with `nexus-php`.

## Pattern 1: SLR search + dedup + export

```php
$service = new NexusService();
$service->registerProvider(ProviderFactory::make('openalex', ['mailto' => 'you@example.com']));
$service->registerProvider(ProviderFactory::make('crossref', ['mailto' => 'you@example.com']));
$service->registerProvider(ProviderFactory::make('arxiv'));

$query = new Query(text: 'vision transformer plant disease', maxResults: 100);
$documents = iterator_to_array($service->search($query));
$clusters = $deduper->deduplicate($documents);
file_put_contents('results.json', (new JsonExporter())->export($documents));
```

## Pattern 2: Citation mapping

```php
$graph = (new CitationGraphBuilder())->buildCitationGraph($documents);
$pageRank = (new NetworkAnalyzer($graph))->computePageRank();
file_put_contents('network.gexf', (new GexfExporter())->export($graph));
```

## Pattern 3: Laravel AI literature assistant

- Register the package service provider.
- Bind `LiteratureSearchTool` into your agent.
- Use `LiteratureSearchAgent` for a dedicated literature-search workflow.

## Pattern 4: PDF enrichment pipeline

- Search and normalize documents.
- Deduplicate.
- Attempt PDF retrieval with `PDFFetcher`.
- Persist both metadata and full-text availability state.
