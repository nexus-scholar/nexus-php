# Core Module

The Core module is the orchestration layer of `nexus-php`. It connects providers, executes searches, merges multi-source flows, and exposes the main entry points developers will use first.

## Files

- `src/Core/NexusService.php`
- `src/Core/ProviderFactory.php`
- `src/Core/SnowballService.php`
- `src/Core/SnowballProviderInterface.php`

## Responsibilities

- Register and manage academic search providers.
- Execute unified searches across one or many providers.
- Support recursive snowballing workflows.
- Provide the main service layer used by plain PHP and Laravel integrations.

## Main classes

### `NexusService`

This is the primary façade for multi-provider search.

Typical workflow:

1. Instantiate the service.
2. Register one or more providers.
3. Create a `Query` object.
4. Iterate over yielded `Document` results.

```php
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Models\Query;

$service = new NexusService();
$service->registerProvider(ProviderFactory::make('openalex', [
    'mailto' => 'you@example.com',
]));
$service->registerProvider(ProviderFactory::make('arxiv'));

$query = new Query(
    text: 'plant disease detection using deep learning',
    maxResults: 100,
    yearMin: 2020,
    yearMax: 2026,
);

foreach ($service->search($query) as $document) {
    echo $document->title . PHP_EOL;
}
```

### `ProviderFactory`

`ProviderFactory` creates provider instances from short names. This keeps calling code simple and standardizes configuration.

```php
$provider = ProviderFactory::make('crossref', [
    'mailto' => 'you@example.com',
]);
```

Supported provider keys normally include:

- `openalex`
- `crossref`
- `arxiv`
- `s2`
- `pubmed`
- `doaj`
- `ieee`

### `SnowballService`

This service expands a seed set of papers using forward and backward citation traversal. It is useful in systematic literature reviews where one relevant paper leads to additional candidate papers.

```php
$results = $snowballService->snowball(
    seeds: $seedDocuments,
    maxDepth: 2,
    direction: 'both'
);
```

## Search patterns

### Search one provider

```php
$service = new NexusService();
$service->registerProvider(ProviderFactory::make('openalex', ['mailto' => 'you@example.com']));

$docs = iterator_to_array($service->search(new Query(text: 'vision transformer agriculture')));
```

### Search multiple providers

```php
$service = new NexusService();
foreach (['openalex', 'crossref', 'arxiv'] as $name) {
    $service->registerProvider(ProviderFactory::make($name, ['mailto' => 'you@example.com']));
}

foreach ($service->search(new Query(text: 'tomato leaf disease detection')) as $doc) {
    // aggregate or stream results
}
```

### Search then deduplicate

```php
$documents = iterator_to_array($service->search($query));
$clusters = $deduper->deduplicate($documents);
```

## Best practices

- Register only the providers you truly need for a specific workflow.
- Use streaming iteration where possible instead of collecting everything immediately.
- Deduplicate after multi-provider searches.
- Persist normalized `Document` results instead of raw provider payloads.
- Add provider-level try/catch handling in application code if you want partial-failure tolerance.

## When to use this module

Use the Core module when you want to:

- build a plain PHP literature-search workflow,
- integrate Nexus into a Laravel service,
- create CLI tools,
- orchestrate multi-step SLR pipelines.
