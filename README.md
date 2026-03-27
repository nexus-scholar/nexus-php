# Nexus PHP

A PHP library for systematic literature reviews (SLR) that searches multiple academic databases simultaneously. This is a port of the [Nexus Research Engine](https://github.com/google/nexus-api) logic.

## Features

- **Multi-Provider Search**: Search across OpenAlex, Crossref, arXiv, Semantic Scholar, PubMed, DOAJ, and IEEE Xplore
- **Configurable**: Easy configuration for providers, rate limits, and deduplication
- **Boolean Query Translation**: Automatically translates complex boolean queries into provider-specific syntax
- **Deduplication**: Built-in conservative deduplication strategy
- **Export**: Export results to CSV, BibTeX, RIS, JSON, and JSONL formats
- **Type-Safe**: Full PHP 8.3+ type declarations
- **Memory Efficient**: Streaming results using PHP Generators
- **Laravel Ready**: Service Provider, Cache, Queue, and Event support
- **AI Integration**: Laravel AI SDK compatible Agents and Tools for AI-powered literature search
- **Prompt Library**: Pre-built system prompts and mega prompts for systematic literature reviews

## Requirements

- PHP 8.3+
- ext-json
- ext-curl
- ext-openssl

## Installation

### Standalone

```bash
composer require nexus/nexus-php
```

### Laravel

```bash
composer require nexus/nexus-php
```

The package includes Laravel integration (ServiceProvider, NexusSearcher, Events, Jobs) and will auto-load when `illuminate/contracts` is available.

## Quick Start (Standalone)

```php
use Nexus\Config\ConfigLoader;
use Nexus\Core\ProviderFactory;
use Nexus\Models\Query;

$config = ConfigLoader::loadDefault();
$provider = ProviderFactory::makeFromConfig('openalex', $config);

$query = new Query(text: 'machine learning', maxResults: 50);
$results = iterator_to_array($provider->search($query));

foreach ($results as $document) {
    echo $document->title . "\n";
}
```

## Laravel Integration

### 1. Register the Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    Nexus\Laravel\NexusServiceProvider::class,
],
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=nexus-config
```

### 3. Configure Environment Variables

Add to `.env`:

```env
NEXUS_MAILTO=your-email@example.com
NEXUS_YEAR_MIN=2020
NEXUS_YEAR_MAX=2026

# Provider API Keys (optional)
NEXUS_PUBMED_API_KEY=your-pubmed-key
NEXUS_IEEE_API_KEY=your-ieee-key

# Cache & Queue
NEXUS_CACHE_TTL=3600
NEXUS_CACHE_STORE=default
NEXUS_QUEUE_CONNECTION=default
NEXUS_QUEUE_NAME=nexus
NEXUS_LOGGING_ENABLED=true
```

### 4. Use NexusSearcher

```php
use Nexus\Laravel\NexusSearcher;
use Nexus\Models\Query;

class ArticleSearchController extends Controller
{
    public function search(NexusSearcher $searcher)
    {
        $query = new Query(
            text: 'machine learning',
            maxResults: 100,
            yearMin: 2022,
            yearMax: 2026
        );

        // Synchronous search with caching
        $results = $searcher->search($query);

        return view('results', compact('results'));
    }
}
```

### 5. Async Queue Search

```php
// Dispatch search job to queue
$jobId = $searcher->searchAsync($query, ['openalex', 'crossref']);

// Later, retrieve from cache
$results = Cache::get('nexus:job:' . $jobId . ':result');
```

### 6. Events

Listen to search events:

```php
// app/Listeners/NexusSearchListener.php
use Nexus\Laravel\Events\SearchCompleted;

class NexusSearchListener
{
    public function handle(SearchCompleted $event): void
    {
        // Log results, notify users, etc.
        Log::info('Search completed', [
            'query' => $event->query->text,
            'results' => $event->getResultCount(),
            'duration' => $event->duration,
        ]);
    }
}
```

### 7. Artisan Command

```bash
# Search from CLI
php artisan nexus:search "machine learning" --max-results=100 --format=json

# Specific providers
php artisan nexus:search "deep learning" --providers=openalex,crossref
```

## Configuration

### Standalone Config (`config/nexus.php`)

```php
return [
    'mailto' => 'your-email@example.com',
    'year_min' => 2020,
    'year_max' => 2026,
    'language' => 'en',

    'providers' => [
        'openalex' => ['enabled' => true, 'rate_limit' => 5.0],
        'crossref' => ['enabled' => true, 'rate_limit' => 1.0],
        'arxiv' => ['enabled' => true, 'rate_limit' => 0.5],
        's2' => ['enabled' => true, 'rate_limit' => 1.0],
        'pubmed' => ['enabled' => true, 'rate_limit' => 3.0],
        'doaj' => ['enabled' => true, 'rate_limit' => 2.0],
        'ieee' => ['enabled' => false, 'rate_limit' => 1.0, 'api_key' => null],
    ],

    'deduplication' => [
        'strategy' => 'conservative',
        'fuzzy_threshold' => 97,
        'max_year_gap' => 1,
    ],

    'cache' => ['ttl' => 3600, 'store' => 'default'],
    'rate_limiter' => ['attempts' => 60, 'decay_seconds' => 60],
    'queue' => ['connection' => 'default', 'name' => 'nexus'],
    'logging' => ['enabled' => true],
];
```

### Laravel Config Options

| Setting      | Environment Variable | Default |
| ------------ | -------------------- | ------- |
| `mailto`     | `NEXUS_MAILTO`       | -       |
| `year_min`   | `NEXUS_YEAR_MIN`     | 2020    |
| `year_max`   | `NEXUS_YEAR_MAX`     | 2026    |
| `cache.ttl`  | `NEXUS_CACHE_TTL`    | 3600    |
| `queue.name` | `NEXUS_QUEUE_NAME`   | nexus   |

## Supported Providers

| Provider         | Alias      | API Required      | Rate Limit              |
| ---------------- | ---------- | ----------------- | ----------------------- |
| OpenAlex         | `openalex` | No                | 10/sec with mailto      |
| Crossref         | `crossref` | No                | 50/sec with mailto      |
| arXiv            | `arxiv`    | No                | 3/sec                   |
| Semantic Scholar | `s2`       | No                | 1/sec                   |
| PubMed           | `pubmed`   | No (optional key) | 3/sec (10/sec with key) |
| DOAJ             | `doaj`     | No                | 2/sec                   |
| IEEE Xplore      | `ieee`     | Yes               | 1/sec                   |

## SSL Certificate Requirements

The library includes a Mozilla CA bundle (`cacert.pem`) for SSL certificate verification. This is automatically used by all HTTP clients.

If you encounter SSL errors, ensure `cacert.pem` exists in the project root:

```bash
curl -L -o cacert.pem https://curl.se/ca/cacert.pem
```

## AI Integration (Laravel AI SDK)

Nexus PHP integrates with the Laravel AI SDK, providing AI agents and tools for literature search capabilities.

## Prompts Library

Comprehensive prompts for systematic literature reviews and academic research:

```php
use Nexus\Prompts\SystemPrompts;
use Nexus\Prompts\MegaPrompts;

// System prompts for AI agents
$instructions = SystemPrompts::SYSTEMATIC_REVIEW_EXPERT;
$instructions = SystemPrompts::RESEARCH_ASSISTANT;
$instructions = SystemPrompts::META_ANALYSIS_ASSISTANT;
$instructions = SystemPrompts::LITERATURE_MAPPING_ASSISTANT;

// Mega prompts for complex workflows
$task = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
$task = MegaPrompts::GAP_ANALYSIS;
$task = MegaPrompts::META_ANALYSIS_PROTOCOL;
$task = MegaPrompts::RESEARCH_BASELINE;
$task = MegaPrompts::ANNOTATED_BIBLIOGRAPHY;
```

See `src/Prompts/README.md` for full documentation.

### Literature Search Tool

The `LiteratureSearchTool` implements the `Laravel\Ai\Contracts\Tool` interface, allowing AI agents to search academic literature:

```php
use Nexus\Laravel\Tools\LiteratureSearchTool;

// Basic usage
$tool = LiteratureSearchTool::make();

// With custom searcher
$tool = LiteratureSearchTool::make(function (Query $query, ?array $providers) {
    // Custom search logic
    return $searcher->search($query, $providers);
});

// Configure options
$tool
    ->withDescription('Search for papers about machine learning')
    ->withProviders(['openalex', 'crossref'])
    ->withAbstract(true)
    ->withAuthors(true);

// Tool schema for AI agents
$schema = $tool->schema($jsonSchema);
// Returns: ['query' => ..., 'max_results' => ..., 'year_min' => ..., 'year_max' => ...]
```

### Literature Search Agent

The `LiteratureSearchAgent` implements the `Laravel\Ai\Contracts\Agent` interface:

```php
use Nexus\Laravel\Agents\LiteratureSearchAgent;

// Create agent
$agent = LiteratureSearchAgent::make();

// Configure agent
$agent
    ->withInstructions('You are a research assistant...')
    ->withProvider('openalex')
    ->withMaxResults(20)
    ->withAbstract(true)
    ->withAuthors(true);

// Use with AI SDK
$response = $agent->prompt('machine learning papers from 2024');

// Stream results
$stream = $agent->stream('deep learning research');

// Queue search for async processing
$queued = $agent->queue('neural network papers');
```

### Integration with AI Orchestration

```php
use Nexus\Laravel\Agents\LiteratureSearchAgent;
use Nexus\Laravel\Tools\LiteratureSearchTool;
use Laravel\Ai\AnonymouAgent;

// Create an AI agent with Nexus tools
$agent = new AnonymousAgent(
    instructions: 'You are a literature research assistant.',
    messages: [],
    tools: [
        LiteratureSearchTool::make()->withProviders(['openalex', 'crossref']),
    ]
);

// Use with Laravel AI
$response = $agent->prompt('Find papers about transformers in NLP');
```

## Testing

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest tests/Integration

# Run AI integration tests
./vendor/bin/pest tests/Laravel

# Run with coverage
./vendor/bin/pest --coverage
```

## Project Structure

```
nexus-php/
├── config/
│   ├── nexus.php           # Standalone configuration
│   └── nexus-laravel.php   # Laravel configuration (uses env())
├── src/
│   ├── Config/             # Configuration classes
│   ├── Core/               # NexusService, ProviderFactory
│   ├── Dedup/              # Deduplication strategies
│   ├── Export/             # Export formats
│   ├── Laravel/            # Laravel integration
│   │   ├── Agents/        # AI agents (LiteratureSearchAgent)
│   │   ├── Commands/      # Artisan commands
│   │   ├── Events/        # Search events
│   │   ├── Jobs/          # Queue jobs
│   │   ├── Listeners/     # Event listeners
│   │   └── Tools/         # AI tools (LiteratureSearchTool)
│   ├── Models/             # Data models
│   ├── Normalization/      # Data normalization
│   ├── Prompts/           # AI prompts and helpers
│   ├── Providers/          # API providers
│   └── Utils/              # Utilities
├── tests/                  # Test files
│   └── Laravel/           # AI integration tests
├── cacert.pem             # Mozilla CA bundle
└── composer.json
```

## License

MIT
