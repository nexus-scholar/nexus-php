# Scholarly Data Providers

This guide provides detailed instructions for implementing and optimizing scholarly data providers in `nexus-php`.

## Lifecycle of a Provider

Every provider must extend `Nexus\Providers\BaseProvider` and implement the following methods:

1.  `translateQuery(Query $query): array`: Converts the generic `Query` object into provider-specific parameters.
2.  `normalizeResponse(mixed $raw): ?Document`: Maps the raw JSON/XML response to a `Nexus\Models\Document` DTO.
3.  `search(Query $query): Generator`: Orchestrates the request, pagination, and yielding of documents.

## Advanced Query Translation

Use the `Nexus\Utils\BooleanQueryTranslator` to handle complex AND/OR/NOT logic.

```php
$fieldMap = [
    QueryField::TITLE->value => 'title',
    QueryField::ABSTRACT->value => 'abstract',
    QueryField::AUTHOR->value => 'authorships.author.display_name',
    // ...
];
$translator = new BooleanQueryTranslator($fieldMap);
$translation = $translator->translate($query);
// $translation['q'] contains the formatted query string
```

## Normalization Details

### DOI Cleaning
Always ensure DOIs are stored without the `https://doi.org/` prefix.
```php
$doi = str_ireplace(['https://doi.org/', 'http://doi.org/', 'doi:'], '', $rawDoi);
```

### OpenAlex Inverted Index
OpenAlex provides abstracts in an inverted index format. Use `FieldExtractor::reconstructAbstract()` to normalize it.
```php
$abstract = FieldExtractor::reconstructAbstract($item['abstract_inverted_index'] ?? []);
```

## Pagination Strategies

### Cursor-Based (OpenAlex)
Use `cursor` and `next_cursor` for deep paging to avoid performance degradation.
```php
$params['cursor'] = '*';
while (true) {
    $response = $this->makeRequest(self::BASE_URL, $params);
    // ... yield results ...
    $params['cursor'] = $response['meta']['next_cursor'] ?? null;
    if (!$params['cursor']) break;
}
```

### Page-Based (Crossref)
Use `offset` and `rows` for standard pagination.
```php
$params['rows'] = 100;
$params['offset'] = 0;
while ($totalRetrieved < $maxResults) {
    $response = $this->makeRequest(self::BASE_URL, $params);
    // ... yield results ...
    $params['offset'] += $params['rows'];
}
```

## Rate Limiting & Politeness
- **Crossref**: Include the `mailto` parameter in your `ProviderConfig` to access the "polite" pool.
- **Semantic Scholar**: Respect the `429 Too Many Requests` status code and use exponential backoff (handled by `BaseProvider` middleware).
