# Getting Started

This guide shows the fastest path from installation to a working multi-provider literature search.

## Install

```bash
composer require nexus/nexus-php
```

## Plain PHP example

```php
require 'vendor/autoload.php';

use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Models\Query;

$service = new NexusService();
$service->registerProvider(ProviderFactory::make('openalex', [
    'mailto' => 'you@example.com',
]));
$service->registerProvider(ProviderFactory::make('crossref', [
    'mailto' => 'you@example.com',
]));

$query = new Query(
    text: 'deep learning for tomato disease detection',
    maxResults: 50,
    yearMin: 2020,
    yearMax: 2026,
);

$documents = iterator_to_array($service->search($query));

echo 'Found ' . count($documents) . ' records' . PHP_EOL;
```

## Deduplicate results

```php
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Models\DeduplicationConfig;

$deduper = new ConservativeStrategy(new DeduplicationConfig(
    fuzzyThreshold: 97,
    maxYearGap: 1,
));

$clusters = $deduper->deduplicate($documents);
```

## Export

```php
use Nexus\Export\BibtexExporter;

file_put_contents('results.bib', (new BibtexExporter())->export($documents));
```
