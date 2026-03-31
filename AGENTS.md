# Nexus PHP — Agent Skills & Task Reference

This file is auto-discovered by AI coding agents (OpenAI Codex, Devin, Amp, etc.)
to understand the project structure and common tasks.

## Project Summary

**nexus-php** is a PHP 8.3+ systematic literature review (SLR) library.
Multi-provider academic search, citation graph analysis, deduplication,
snowballing, PDF retrieval, and Laravel AI integration.

- **Package:** `nexus/nexus-php`
- **Namespace:** `Nexus\`
- **PHP:** ^8.3
- **Tests:** Pest 3 (`vendor/bin/pest`)
- **Code style:** Laravel Pint (`vendor/bin/pint`)

---

## Runnable Tasks

| Task | Command |
|---|---|
| Run all tests | `vendor/bin/pest` |
| Run with coverage | `vendor/bin/pest --coverage` |
| Fix code style | `vendor/bin/pint` |
| Search via CLI | `php artisan nexus:search "query" --format=json` |
| List agent skills | `php nexus-skills/discover.php` |
| Machine-readable skills | `php nexus-skills/discover.php --json` |
| Install dependencies | `composer install` |

---

## Skill: Add a Provider

**Trigger:** User asks to add support for a new academic database (e.g., Scopus, CORE, BASE).

**Steps:**
1. Create `src/Providers/{Name}Provider.php` extending `Nexus\Providers\BaseProvider`
2. Implement the three abstract methods:
   - `translateQuery(Query $query): array`
   - `normalizeResponse(mixed $raw): ?Document`
   - `search(Query $query): Generator` — yield `Document` objects
3. Register in `ProviderFactory::createProvider()` match in `src/Core/ProviderFactory.php`
4. Add config defaults in `src/Config/ProviderSettings.php`
5. Create `tests/Providers/{Name}ProviderTest.php` with mocked HTTP responses

**Minimal template:**
```php
namespace Nexus\Providers;

use Generator;
use Nexus\Models\Document;
use Nexus\Models\Query;

class ScopusProvider extends BaseProvider
{
    public function search(Query $query): Generator
    {
        $params = $this->translateQuery($query);
        $raw = $this->makeRequest('https://api.elsevier.com/content/search/scopus', $params, [
            'X-ELS-APIKey' => $this->config->apiKey,
        ]);
        foreach ($raw['search-results']['entry'] ?? [] as $item) {
            $doc = $this->normalizeResponse($item);
            if ($doc !== null) yield $doc;
        }
    }

    protected function translateQuery(Query $query): array
    {
        return ['query' => $query->text, 'count' => $query->maxResults];
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        if (empty($raw['dc:title'])) return null;
        return new Document(
            title: $raw['dc:title'],
            year: (int) substr($raw['prism:coverDate'] ?? '', 0, 4),
            provider: 'scopus',
            providerId: $raw['dc:identifier'] ?? '',
        );
    }
}
```

---

## Skill: Add an Exporter

**Trigger:** User wants to export results to a new format (e.g., EndNote XML, Markdown).

**Steps:**
1. Create `src/Export/{Format}Exporter.php` implementing `Nexus\Export\ExporterInterface`
2. Extend `Nexus\Export\BaseExporter` for shared helpers

**Interface:**
```php
interface ExporterInterface {
    public function export(array $documents): string;
}
```

---

## Skill: Add a Deduplication Strategy

**Trigger:** User needs a new deduplication algorithm (e.g., aggressive title-only).

**Steps:**
1. Create `src/Dedup/{Name}Strategy.php` extending `Nexus\Dedup\DeduplicationStrategy`
2. Implement `deduplicate(array $documents, ?callable $progressCallback = null): array`
3. Add the strategy name to `Nexus\Models\DeduplicationStrategyName` enum

---

## Skill: Add a CLI Command

**Trigger:** User wants a new `php artisan nexus:*` command.

**Steps:**
1. Create `src/Laravel/Commands/{Name}Command.php` extending `Illuminate\Console\Command`
2. Define `$signature` and `$description`
3. Register in `NexusServiceProvider::boot()` inside `$this->commands([...])`

---

## Skill: Write a Test

**Pattern (Pest + mocked Guzzle):**
```php
use Nexus\Providers\OpenAlexProvider;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

it('returns documents from a mocked API response', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'results' => [['title' => 'Test Paper', 'publication_year' => 2023]],
            'meta'    => ['count' => 1],
        ]))
    ]);
    $client   = new Client(['handler' => HandlerStack::create($mock)]);
    $config   = new ProviderConfig(name: 'openalex', enabled: true, rateLimit: 10.0, timeout: 30);
    $provider = new OpenAlexProvider($config, $client);

    $results = iterator_to_array($provider->search(new Query(text: 'test')));
    expect($results)->toHaveCount(1);
    expect($results[0]->title)->toBe('Test Paper');
});
```

---

## File Map

| What you want | Where it is |
|---|---|
| Search orchestrator | `src/Core/NexusService.php` |
| Provider base class | `src/Providers/BaseProvider.php` |
| Provider factory | `src/Core/ProviderFactory.php` |
| Document model | `src/Models/Document.php` |
| Dedup logic | `src/Dedup/ConservativeStrategy.php` |
| Citation graph | `src/CitationAnalysis/CitationGraphBuilder.php` |
| Network metrics | `src/CitationAnalysis/NetworkAnalyzer.php` |
| PDF fetcher | `src/Retrieval/PDFFetcher.php` |
| Config loader | `src/Config/ConfigLoader.php` |
| Laravel provider | `src/Laravel/NexusServiceProvider.php` |
| Artisan search command | `src/Laravel/Commands/SearchCommand.php` |
| Artisan skills command | `src/Laravel/Commands/SkillsCommand.php` |
| AI agent | `src/Laravel/Agents/LiteratureSearchAgent.php` |
| AI tool | `src/Laravel/Tools/LiteratureSearchTool.php` |
| Prompt library | `src/Prompts/SystemPrompts.php` |
| Agent skills manifest | `nexus-skills/skills.json` |
| CLI skill discovery | `nexus-skills/discover.php` |
