# Snowballing Feature

Snowballing is a technique used in systematic literature reviews to find additional relevant papers by exploring the citation network of seed documents. This package implements both **forward snowballing** (finding papers that cite your seed documents) and **backward snowballing** (finding papers referenced by your seed documents).

## Configuration

### SnowballConfig

Create a `SnowballConfig` object to configure the snowballing behavior:

```php
use Nexus\Models\SnowballConfig;

$config = new SnowballConfig(
    forward: true,        // Enable forward snowballing (citing papers)
    backward: true,       // Enable backward snowballing (referenced papers)
    maxCitations: 100,   // Maximum citing papers to fetch per document
    maxReferences: 50,   // Maximum references to fetch per document
    depth: 1             // Recursion depth (0 = no recursion)
);
```

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `forward` | bool | `true` | Whether to fetch papers that cite the seed document |
| `backward` | bool | `true` | Whether to fetch papers referenced by the seed document |
| `maxCitations` | int | `100` | Maximum number of citing papers to retrieve per document |
| `maxReferences` | int | `50` | Maximum number of references to retrieve per document |
| `depth` | int | `1` | Recursion depth for snowballing new documents (0 = disabled) |

## Usage

### Basic Usage

```php
use Nexus\Config\ConfigLoader;
use Nexus\Core\ProviderFactory;
use Nexus\Core\SnowballService;
use Nexus\Models\SnowballConfig;
use Nexus\Models\Document;

// Load configuration
$config = ConfigLoader::loadDefault();

// Create providers
$openalex = ProviderFactory::makeFromConfig('openalex', $config);
$semanticScholar = ProviderFactory::makeFromConfig('s2', $config);

// Create snowball config
$snowballConfig = new SnowballConfig(
    forward: true,
    backward: true,
    maxCitations: 50,
    maxReferences: 25,
    depth: 1
);

// Create snowball service (now supports variadic providers)
$snowballService = new SnowballService($snowballConfig, $openalex, $semanticScholar);

// Your seed document (must have openalexId or s2Id for snowballing)
$seedDocument = new Document(
    title: "Your Seed Paper Title",
    externalIds: new \Nexus\Models\ExternalIds(
        openalexId: "W1234567890"  // OpenAlex ID or s2Id required
    )
);

// Existing documents from your search results
$existingDocuments = [$doc1, $doc2, /* ... */];

// Perform snowballing
$newDocuments = $snowballService->snowball($seedDocument, $existingDocuments);

// $newDocuments contains unique documents not in $existingDocuments
```

### Multi-Document Snowballing

For snowballing multiple seed documents at once:

```php
$seedDocuments = [$doc1, $doc2, $doc3];

$newDocuments = $snowballService->snowballMultiple($seedDocuments, $existingDocuments);
```

### Recursive Snowballing

To recursively snowball new documents found in previous iterations:

```php
$config = new SnowballConfig(
    forward: true,
    backward: true,
    maxCitations: 20,
    maxReferences: 10,
    depth: 2  // Will snowball twice: seed -> level1 -> level2
);

$snowballService = new SnowballService($config, $openalex, $semanticScholar);
$newDocuments = $snowballService->snowball($seedDocument, $existingDocuments);
```

> **Warning:** Higher depth values will result in more API calls and longer execution times.

## Provider Requirements

### OpenAlex Provider

The OpenAlex provider requires either:
- **OpenAlex ID** (e.g., `W4391582407`) - extracted from `Document->externalIds->openalexId`
- Works without any ID but will return no results

### Semantic Scholar Provider

The Semantic Scholar provider requires either:
- **Semantic Scholar Paper ID** (e.g., `00000c33779acab142af6c7a6dae8b36fac0805d`) - extracted from `Document->externalIds->s2Id`
- Works without any ID but will return no results

For best results, ensure your seed documents have both OpenAlex and Semantic Scholar IDs. The service will query both providers and merge the results.

## Integration with Search Results

### Complete Workflow Example

```php
use Nexus\Config\ConfigLoader;
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Core\SnowballService;
use Nexus\Models\Query;
use Nexus\Models\SnowballConfig;
use Nexus\Export\JsonExporter;

// 1. Load config and create providers
$config = ConfigLoader::loadDefault();
$openalex = ProviderFactory::makeFromConfig('openalex', $config);
$s2 = ProviderFactory::makeFromConfig('s2', $config);

// 2. Run searches
$service = new NexusService();
$service->registerProvider($openalex);
$service->registerProvider($s2);

$queries = [
    new Query(text: 'machine learning', maxResults: 10),
    new Query(text: 'deep learning transformer', maxResults: 10),
];

$allResults = [];
foreach ($queries as $query) {
    $results = iterator_to_array($service->search($query));
    $allResults = array_merge($allResults, $results);
}

// 3. Save results
$exporter = new JsonExporter('./output');
$exporter->exportDocuments($allResults, 'search_results');

// 4. Select seed document (highest cited)
usort($allResults, fn($a, $b) => ($b->citedByCount ?? 0) <=> ($a->citedByCount ?? 0));
$seedDocument = $allResults[0];

// 5. Perform snowballing
$snowballConfig = new SnowballConfig(
    forward: true,
    backward: true,
    maxCitations: 50,
    maxReferences: 25,
    depth: 1
);

$snowballService = new SnowballService($snowballConfig, $openalex, $s2);
$newDocuments = $snowballService->snowball($seedDocument, $allResults);

// 6. Save snowball results
$exporter->exportDocuments($newDocuments, 'snowball_results');

// 7. Combine all documents and deduplicate
$combinedDocs = array_merge($allResults, $newDocuments);
// ... use ConservativeStrategy to deduplicate
```

## API Reference

### SnowballService

```php
class SnowballService
{
    public function __construct(
        SnowballConfig $config,
        OpenAlexProvider $openalex,
        SemanticScholarProvider $semanticScholar
    ) {}

    /**
     * Snowball a single document
     *
     * @param Document $seed The seed document to snowball
     * @param Document[] $existingDocuments Existing documents to filter against
     * @return Document[] Unique new documents not in existing set
     */
    public function snowball(Document $seed, array $existingDocuments): array {}

    /**
     * Snowball multiple seed documents
     *
     * @param Document[] $seeds Array of seed documents
     * @param Document[] $existingDocuments Existing documents to filter against
     * @param int $currentDepth Current recursion depth (internal use)
     * @return Document[] Unique new documents from all seeds
     */
    public function snowballMultiple(array $seeds, array $existingDocuments, int $currentDepth = 0): array {}
}
```

### Provider Methods

#### OpenAlexProvider

```php
// Get papers that cite a work
public function getCitingWorks(string $openalexId, int $limit = 100): Generator;

// Get papers referenced by a work
public function getReferencedWorks(string $openalexId, int $limit = 50): Generator;

// Get a single work by ID
public function getWorkById(string $id): ?Document;
```

#### SemanticScholarProvider

```php
// Get papers that cite a paper
public function getCitingPapers(string $paperId, int $limit = 100): Generator;

// Get papers referenced by a paper
public function getReferences(string $paperId, int $limit = 50): Generator;

// Get a single paper by ID
public function getPaperById(string $paperId): ?Document;
```

## Testing

Run the integration tests to verify snowballing works:

```bash
# Run all snowball tests
vendor/bin/phpunit tests/Integration/SnowballIntegrationTest.php

# Run specific test
vendor/bin/phpunit --filter test_select_document_and_snowball tests/Integration/SnowballIntegrationTest.php
```
