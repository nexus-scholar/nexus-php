# Provider Development Guide

This guide explains how to add a new scholarly API provider to `nexus-php`.

## Step 1: Create the provider class

Create `src/Providers/MyProvider.php` and extend `BaseProvider`.

```php
class MyProvider extends BaseProvider
{
    protected function translateQuery(Query $query): array
    {
        return ['q' => $query->text, 'limit' => $query->maxResults];
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
        $response = $this->makeRequest('https://api.example.com/search', $this->translateQuery($query));

        foreach ($response['results'] ?? [] as $row) {
            $doc = $this->normalizeResponse($row);
            if ($doc !== null) {
                yield $doc;
            }
        }
    }
}
```

## Step 2: Register the provider

Add the provider key to `ProviderFactory`.

## Step 3: Add config defaults

Update provider settings or config docs so users know how to configure auth, rate limits, and endpoints.

## Step 4: Test it

Create `tests/Providers/MyProviderTest.php` using mocked HTTP responses.

## Checklist

- Query translation works.
- Response normalization returns `Document`.
- Missing records are skipped safely.
- Auth and rate limits are documented.
