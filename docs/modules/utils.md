# Utils Module

The Utils module contains reusable helpers and infrastructure classes used across search, parsing, networking, deduplication, and error handling.

## Files

Key files commonly include:

- `src/Utils/RateLimiter.php`
- `src/Utils/Retry.php`
- `src/Utils/UnionFind.php`
- `src/Utils/BooleanQueryTranslator.php`
- `src/Utils/FieldExtractor.php`
- `src/Utils/QueryParser.php`
- `src/Utils/QueryToken.php`
- `src/Utils/Exceptions/*`

## Important utilities

### `RateLimiter`

Used to throttle outbound API traffic and avoid provider bans or quota spikes.

### `Retry`

Wraps transient-failure handling so network code can retry appropriately.

### `UnionFind`

Supports efficient grouping logic for deduplication clusters.

### `BooleanQueryTranslator`

Helps convert user-level Boolean expressions into provider-compatible syntax.

## Example

```php
$translated = $translator->translate('(tomato OR potato) AND disease');
```

## Exception layer

The package defines domain-specific exceptions such as:

- configuration errors,
- validation errors,
- provider errors,
- authentication errors,
- rate-limit errors,
- export errors.

## Best practices

- Throw domain exceptions instead of generic runtime exceptions when possible.
- Centralize retry and throttling policy.
- Reuse `UnionFind` for cluster-building tasks instead of custom ad hoc logic.
