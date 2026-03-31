# Nexus PHP — Coding Agent Instructions

> These instructions are auto-discovered by GitHub Copilot, Cursor, and other coding agents
> that support `.github/copilot-instructions.md`.

## What This Package Is

`nexus-php` is a PHP 8.3+ library for **Systematic Literature Reviews (SLR)**. It searches
multiple academic databases simultaneously, deduplicates results, analyzes citation networks,
performs snowballing, retrieves PDFs, and exports to BibTeX/RIS/CSV/JSON.

**Namespace:** `Nexus\`  
**Autoload root:** `src/`  
**Laravel integration:** `src/Laravel/`  
**Test framework:** Pest 3

---

## Core Architecture

```
src/
├── Core/             NexusService (orchestrator), ProviderFactory, SnowballService
├── Providers/        BaseProvider + 7 academic API providers
├── Models/           Document, Author, Query, ExternalIds, DocumentCluster
├── Dedup/            ConservativeStrategy (Union-Find fuzzy deduplication)
├── CitationAnalysis/ CitationGraphBuilder, NetworkAnalyzer, CoCitationAnalyzer
├── Export/           BibTeX, RIS, CSV, JSON, JSONL exporters
├── Retrieval/        PDFFetcher with multi-source fallback
├── Normalization/    AuthorParser, DateParser, IDExtractor, ResponseNormalizer
├── Config/           ConfigLoader (PHP/JSON/YAML), NexusConfig
├── Utils/            RateLimiter, Retry, UnionFind, BooleanQueryTranslator
├── Visualization/    GEXF (Gephi), GraphML, Cytoscape.js exporters
├── Prompts/          SystemPrompts, MegaPrompts, PromptHelpers
└── Laravel/          ServiceProvider, Commands, Jobs, Events, Agents, Tools
```

---

## Provider Registry

| Key | Class | Auth |
|---|---|---|
| `openalex` | `OpenAlexProvider` | None (mailto polite pool) |
| `crossref` | `CrossrefProvider` | None (mailto recommended) |
| `arxiv` | `ArxivProvider` | None |
| `s2` | `SemanticScholarProvider` | Optional API key |
| `pubmed` | `PubMedProvider` | Optional API key |
| `doaj` | `DOAJProvider` | None |
| `ieee` | `IEEEProvider` | API key required |

---

## Key Usage Patterns

### Searching
```php
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Models\Query;

$service = new NexusService();
$service->registerProvider(ProviderFactory::make('openalex', ['mailto' => 'you@example.com']));
$service->registerProvider(ProviderFactory::make('arxiv'));

$query = new Query(text: 'plant disease detection deep learning', maxResults: 50, yearMin: 2020);
foreach ($service->search($query) as $document) {
    // $document is Nexus\Models\Document
}
```

### Deduplication
```php
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Models\DeduplicationConfig;

$clusters = (new ConservativeStrategy(
    new DeduplicationConfig(fuzzyThreshold: 97, maxYearGap: 1)
))->deduplicate($documents);
```

### Citation Network
```php
use Nexus\CitationAnalysis\CitationGraphBuilder;
use Nexus\CitationAnalysis\NetworkAnalyzer;

$graph = (new CitationGraphBuilder())->buildCitationGraph($documents);
$top10 = (new NetworkAnalyzer($graph))->findInfluentialPapers(limit: 10);
```

### Export
```php
use Nexus\Export\BibtexExporter;

file_put_contents('results.bib', (new BibtexExporter())->export($documents));
```

### Laravel Artisan
```bash
php artisan nexus:search "plant disease" --providers=openalex,arxiv --max-results=100 --format=json
php artisan nexus:skills                  # list all agent skills
php artisan nexus:skills --json           # machine-readable manifest
php artisan nexus:skills --skill=search   # detail for one skill
```

---

## Adding a New Provider

1. Create `src/Providers/MyProvider.php` extending `BaseProvider`
2. Implement three abstract methods:
   - `translateQuery(Query $query): array`
   - `normalizeResponse(mixed $raw): ?Document`
   - `search(Query $query): Generator` — must `yield Document` objects
3. Register in `ProviderFactory::createProvider()` match expression
4. Add config defaults in `src/Config/ProviderSettings.php`
5. Create `tests/Providers/MyProviderTest.php` with mocked Guzzle responses

## Adding a New Exporter

1. Implement `Nexus\Export\ExporterInterface` → `export(array $documents): string`
2. Extend `Nexus\Export\BaseExporter` for shared helpers
3. Place in `src/Export/`

## Adding a CLI Command

1. Create `src/Laravel/Commands/{Name}Command.php` extending `Illuminate\Console\Command`
2. Register in `NexusServiceProvider::boot()` inside `$this->commands([...])`

---

## Configuration (PHP / JSON / YAML all supported)

```yaml
# config/nexus.yml
mailto: "you@example.com"
year_min: 2020
year_max: 2026
providers:
  openalex:
    enabled: true
    rate_limit: 5.0
  arxiv:
    enabled: true
    rate_limit: 0.5
deduplication:
  strategy: conservative
  fuzzy_threshold: 97
```

---

## Testing

```bash
vendor/bin/pest                    # all tests
vendor/bin/pest tests/Providers/   # provider suite only
vendor/bin/pest --coverage         # with coverage
```

Tests use **mocked Guzzle clients** — never hit real APIs in unit tests.

---

## Conventions

- All providers yield `Document` via `Generator` — never return arrays
- `null` from `normalizeResponse()` means skip (malformed response)
- Rate limiting is per-provider via token-bucket `RateLimiter`
- `ExternalIds` is always initialized (never null on a `Document`)
- Dedup fusion priority: `crossref > pubmed > openalex > s2 > arxiv`
- Laravel bindings: `NexusService::class` (singleton), `'nexus.searcher'` (bind)

---

## Common Mistakes to Avoid

- ❌ Do NOT add `composer.lock` (this is a library package)
- ❌ Do NOT commit `cacert.pem` — use system bundle or `composer/ca-bundle`
- ❌ Do NOT hardcode email/API keys in committed config files
- ❌ Do NOT return arrays from `search()` — must be `Generator`
- ❌ Do NOT call `app()` inside `src/Core/` or `src/Providers/`
