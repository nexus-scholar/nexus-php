# Normalization Module

The Normalization module converts inconsistent provider payloads into stable internal structures. This is what makes multi-provider search usable as one coherent system.

## Files

- `src/Normalization/AuthorParser.php`
- `src/Normalization/DateParser.php`
- `src/Normalization/IDExtractor.php`
- `src/Normalization/ResponseNormalizer.php`

## Why normalization matters

Different scholarly APIs represent the same concepts differently:

- author names may be arrays, strings, or nested objects,
- dates may be full timestamps, years, or partial dates,
- IDs may be embedded in URLs or mixed structures.

Normalization ensures downstream modules receive consistent values.

## Typical responsibilities

### `AuthorParser`

Converts provider-specific author payloads into normalized author records.

### `DateParser`

Extracts consistent year or date values from provider responses.

### `IDExtractor`

Finds DOI, PMID, arXiv ID, OpenAlex IDs, and related identifiers from raw metadata.

### `ResponseNormalizer`

Coordinates field-level normalization into a final `Document` model.

## Example workflow

```php
$normalized = $normalizer->normalize($rawPayload, provider: 'openalex');
```

## Best practices

- Keep provider-specific parsing logic isolated from business logic.
- Normalize as early as possible.
- Preserve raw source values only when needed for debugging.
- Write tests for malformed and partially complete payloads.
