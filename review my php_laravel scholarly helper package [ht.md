<img src="https://r2cdn.perplexity.ai/pplx-full-logo-primary-dark%402x.png" style="height:64px;margin-right:32px"/>

# review my php/laravel scholarly helper package [https://github.com/nexus-scholar/nexus-php](https://github.com/nexus-scholar/nexus-php)

Here's a thorough code review of [nexus-scholar/nexus-php](https://github.com/nexus-scholar/nexus-php), a PHP port of the Nexus Research Engine. The package is architecturally solid overall, with several areas worth addressing.

***

## Overall Architecture

The package is well-structured with clear separation of concerns across 13 namespaced modules: `Core`, `Providers`, `Dedup`, `Models`, `Export`, `Retrieval`, `Normalization`, `CitationAnalysis`, `Visualization`, `Utils`, `Config`, `Prompts`, and `Laravel` . The Laravel integration is done cleanly via a dedicated `src/Laravel/` namespace with a `ServiceProvider`, `Commands`, `Jobs`, `Events`, `Listeners`, `Agents`, and `Tools`  — this is a mature organizational pattern for a Laravel package.

***

## 🟢 Strengths

**Provider pattern is excellent.** `BaseProvider` defines a clean abstract contract (`search`, `translateQuery`, `normalizeResponse`) and all concrete providers extend it consistently . The use of PHP `Generator` for streaming results is the right choice for large scholarly result sets — it avoids loading thousands of documents into memory at once.

**Cursor-based pagination is properly implemented.** `OpenAlexProvider` correctly handles OpenAlex's cursor pagination with `next_cursor`, breaking on empty results or repeated cursors . This is a common pitfall in scholarly API clients and you've handled it well.

**Deduplication is sophisticated.** The `DeduplicationStrategy` multi-signal approach (DOI, arXiv ID, OpenAlex ID, S2 ID, normalized title) is academically sound. The `fuseDocuments` method's provider priority weighting (`crossref=5`, `pubmed=4`, `openalex=3`, etc.) with a citation count tiebreaker is a smart heuristic .

**Snowball sampling is properly recursive.** `SnowballService::snowballMultiple` tracks depth and accumulates `$allExisting` across seeds to prevent re-fetching already-seen documents . The separation of forward/backward citation snowballing controlled by `SnowballConfig` is clean.

**`cacert.pem` bundled with path fallbacks** in `BaseProvider::findCACertPath()` is a practical workaround for Windows environments .

***

## 🔴 Issues \& Bugs

**1. `SkillsCommand` not imported in `NexusServiceProvider`.**
In `boot()`, `SkillsCommand::class` is registered but never imported at the top of the file . This will throw a `Class not found` error at runtime when running in console. Add:

```php
use Nexus\Laravel\Commands\SkillsCommand;
```

**2. `OpenAlexProvider` constructor uses untyped parameters.**
The constructor signature `public function __construct($config, $client = null)` drops the type hints from `BaseProvider` . This undermines PHP 8.3's type safety. It should be:

```php
public function __construct(ProviderConfig $config, ?Client $client = null)
```

**3. Hardcoded `type:article|review` filter in `OpenAlexProvider::translateQuery`.**
The filter `$filters[] = "type:article|review"` is always applied , silently excluding preprints, conference papers, book chapters, and dissertations. This is a serious issue for systematic literature reviews. It should be configurable via `Query` or `ProviderConfig`.

**4. `SnowballService` dedup config is hardcoded.**
The `DeduplicationConfig` inside `SnowballService::__construct` hardcodes `fuzzyThreshold: 97` and `maxYearGap: 1` with no way to override them from outside . Even though `setDeduplicationStrategy()` allows swapping strategies, the default config values should flow from `SnowballConfig` or a constructor parameter.

**5. `makeRequest` in `BaseProvider` always JSON-decodes.**
The method always calls `json_decode()` , but `ArxivProvider` works with XML responses. Check how ArXiv is handled — if it overrides `makeRequest`, that's fine, but it's an invisible contract violation that could confuse contributors.

**6. `composer.json` version is hardcoded `"1.0.0"`.**
Packages distributed via Packagist should not have a `version` field in `composer.json`  — Packagist derives the version from Git tags. This can cause version conflicts.

***

## 🟡 Improvements \& Suggestions

**7. `cacert.pem` committed to the repository (226 KB).**
Bundling the CA certificate  makes sense for Windows support, but it will go stale. Consider either: (a) making it optional and documenting that users can set `verify` themselves, or (b) pointing to the system's CA bundle when available and only falling back to the bundled file.

**8. `DeduplicationStrategy::normalizeTitle` removes all non-word characters.**
The regex `/[^\w\s]/u` strips hyphens, colons, and parentheses . Titles like "COVID-19: A Review" and "COVID19 A Review" will now normalize identically even if they're genuinely different papers. Consider keeping hyphens or using a more targeted strip (punctuation only).

**9. `fuseDocuments` sorts in-place, mutating the input array.**
`usort($documents, ...)` sorts the passed array by reference . If the caller holds a reference to that array, the order will be mutated unexpectedly. Use a local copy: `$sorted = $documents; usort($sorted, ...);`.

**10. `OpenAlexProvider::getCitingWorks` / `getReferencedWorks` duplicate the pagination loop.**
Both methods share identical pagination logic . Extract a private `paginateWorks(array $params, int $limit): Generator` method and call it from both.

**11. Author name splitting is fragile.**
In `OpenAlexProvider::parseAuthors`, the code assumes the last space-separated token is the family name . This breaks for names like "Jean-Pierre de la Fontaine". Consider using the `display_name` as-is or leveraging the separate `author.id` to look up structured name data.

**12. `minimum-stability: dev` in `composer.json`.**
With `mbsoft31/graph-core` and `mbsoft31/graph-algorithms` pinned at `^1.0`, setting `minimum-stability: dev`  is unnecessarily broad and could pull in unstable dependencies in user projects. Use `stable` unless you specifically need dev releases.

**13. No interface for `ProviderFactory`.**
`ProviderFactory::make()` uses a hardcoded `match` expression . If a user wants to register a custom provider (e.g., a Scopus adapter), they cannot without modifying library code. Consider a registry pattern or a `ProviderFactory::register(string $name, callable $factory)` hook.

***

## Testing

The project uses Pest 3 with PHPUnit configuration , which is a good choice. However, from what's visible, there are no mocked HTTP tests against the provider classes. Given that `BaseProvider::makeRequest` makes live Guzzle calls, you should add Guzzle mock handler tests to ensure providers correctly parse API responses without hitting real endpoints.

***

## Priority Fix List

| Priority | Issue |
| :-- | :-- |
| 🔴 Critical | `SkillsCommand` missing `use` import → runtime crash |
| 🔴 Critical | Hardcoded `type:article|review` filter excludes valid document types |
| 🔴 High | `OpenAlexProvider` constructor missing type hints |
| 🟡 Medium | `fuseDocuments` mutates input array |
| 🟡 Medium | `cacert.pem` will go stale; document update strategy |
| 🟡 Medium | Remove `"version"` from `composer.json` for Packagist |
| 🟢 Low | Duplicate pagination loops in `OpenAlexProvider` |
| 🟢 Low | Custom provider registration mechanism |

Overall this is a well-architected package with clear research domain expertise behind it. The critical bugs are few and fixable quickly. The architectural decisions — Generator-based streaming, multi-signal dedup, cursor pagination, provider abstraction — are all solid choices that will serve the scholarly use case well.

