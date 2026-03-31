# Providers Module

The Providers module contains adapters for external scholarly APIs. Each provider translates a generic `Query` into provider-specific HTTP parameters, executes remote requests, and normalizes raw responses into `Document` objects.

## Files

- `src/Providers/BaseProvider.php`
- `src/Providers/OpenAlexProvider.php`
- `src/Providers/CrossrefProvider.php`
- `src/Providers/ArxivProvider.php`
- `src/Providers/SemanticScholarProvider.php`
- `src/Providers/PubMedProvider.php`
- `src/Providers/DOAJProvider.php`
- `src/Providers/IEEEProvider.php`

## Provider lifecycle

Each provider generally follows the same pattern:

1. Accept provider configuration.
2. Translate a generic `Query`.
3. Call the remote API.
4. Normalize each record.
5. Yield `Document` objects.

## Base contract

Custom providers should extend `BaseProvider` and implement three methods:

```php
abstract protected function translateQuery(Query $query): array;
abstract protected function normalizeResponse(mixed $raw): ?Document;
abstract public function search(Query $query): Generator;
```

## Example usage

```php
use Nexus\Core\ProviderFactory;

$openAlex = ProviderFactory::make('openalex', [
    'mailto' => 'you@example.com',
    'rate_limit' => 5.0,
]);
```

## Provider notes

### OpenAlex

Good for broad academic discovery, metadata enrichment, and referenced-works traversal.

### Crossref

Strong DOI coverage and publisher metadata. Often useful for metadata completion and DOI-centric workflows.

### arXiv

Best for preprints and computer science, physics, mathematics, and related open-access domains.

### Semantic Scholar

Useful for citation-rich metadata and AI/CS-heavy coverage.

### PubMed

Best for biomedical and life sciences literature.

### DOAJ

Focused on open-access journals.

### IEEE

Useful for engineering and computing literature, usually requiring an API key.

## Writing a custom provider

```php
namespace Nexus\Providers;

use Generator;
use Nexus\Models\Document;
use Nexus\Models\Query;

class MyProvider extends BaseProvider
{
    protected function translateQuery(Query $query): array
    {
        return [
            'q' => $query->text,
            'limit' => $query->maxResults,
        ];
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        if (empty($raw['title'])) {
            return null;
        }

        return new Document(
            title: $raw['title'],
            year: (int) ($raw['year'] ?? 0),
            provider: 'myprovider',
            providerId: (string) ($raw['id'] ?? ''),
        );
    }

    public function search(Query $query): Generator
    {
        $payload = $this->makeRequest('https://api.example.com/search', $this->translateQuery($query));

        foreach ($payload['results'] ?? [] as $row) {
            $doc = $this->normalizeResponse($row);
            if ($doc !== null) {
                yield $doc;
            }
        }
    }
}
```

## Best practices

- Always normalize into a `Document`, never expose raw provider structures upstream.
- Return `null` from `normalizeResponse()` for malformed records.
- Respect rate limits and provider API terms.
- Put auth keys in environment variables, not committed config files.
- Prefer provider-specific tests with mocked responses.
