# Models Module

The Models module defines the normalized data structures used across the entire package. These models decouple application logic from provider-specific payload formats.

## Files

- `src/Models/Document.php`
- `src/Models/Author.php`
- `src/Models/ExternalIds.php`
- `src/Models/Query.php`
- `src/Models/QueryField.php`
- `src/Models/DocumentCluster.php`
- `src/Models/ProviderConfig.php`
- `src/Models/DeduplicationConfig.php`
- `src/Models/SnowballConfig.php`

## Key models

### `Document`

`Document` is the package's central normalized record. It typically contains bibliographic metadata such as title, abstract, year, authors, identifiers, source provider, and provider-specific IDs.

```php
$document = new Document(
    title: 'A survey on plant disease detection',
    abstract: 'This paper reviews ...',
    year: 2024,
    provider: 'openalex',
    providerId: 'W123456789',
    doi: '10.1000/example',
);
```

### `Author`

Represents a normalized author with fields like full name and possibly split components depending on implementation.

### `ExternalIds`

Stores normalized IDs such as DOI, PMID, PMCID, arXiv ID, OpenAlex ID, and Semantic Scholar IDs.

### `Query`

Represents a provider-agnostic search request.

```php
$query = new Query(
    text: 'systematic literature review plant disease detection',
    maxResults: 50,
    yearMin: 2020,
    yearMax: 2026,
    language: 'en',
);
```

### `DocumentCluster`

Represents a deduplicated cluster of logically equivalent documents, often with one fused canonical document.

## Usage pattern

A typical pipeline looks like this:

1. Build a `Query`.
2. Search providers.
3. Normalize results into `Document` objects.
4. Cluster duplicates into `DocumentCluster` structures.
5. Export or analyze the fused documents.

## Best practices

- Treat these models as your stable application-facing schema.
- Store normalized models in your database or pipeline state, not raw API payloads.
- Use `ExternalIds` to reconcile the same paper across providers.
- Favor canonical fused `Document` records for downstream analysis.
