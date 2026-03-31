# Deduplication Module

The Deduplication module identifies duplicate or near-duplicate documents across providers and clusters them into canonical groups. This is essential for systematic reviews because the same paper often appears in multiple databases.

## Files

- `src/Dedup/DeduplicationStrategy.php`
- `src/Dedup/ConservativeStrategy.php`

## Goals

- Merge identical papers from different providers.
- Reduce noise before screening and export.
- Preserve multiple identifiers while exposing one canonical record.

## Main concepts

### Exact matching

The strategy typically uses strong identifiers first, such as:

- DOI
- arXiv ID
- PubMed-related IDs
- Provider IDs where meaningful

### Fuzzy matching

When strong IDs are absent, the strategy can compare title similarity with additional constraints such as publication year tolerance.

## Example usage

```php
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Models\DeduplicationConfig;

$config = new DeduplicationConfig(
    fuzzyThreshold: 97,
    maxYearGap: 1,
);

$deduper = new ConservativeStrategy($config);
$clusters = $deduper->deduplicate($documents);
```

## Working with clusters

```php
foreach ($clusters as $cluster) {
    $canonical = $cluster->canonical ?? null;
    $members = $cluster->documents;
}
```

## Suggested workflow

1. Search across several providers.
2. Convert the generator to an array only when needed.
3. Deduplicate immediately.
4. Persist clusters and a fused canonical record.
5. Use the canonical set for export and analysis.

## Tuning advice

- Use a stricter threshold for production pipelines where false merges are costly.
- Use a slightly looser threshold only when metadata quality is poor.
- Always inspect edge cases with missing DOI and near-identical titles.
- Keep year-gap tolerance conservative.

## Recommended screening pattern

- Keep both canonical and member-level provenance.
- Show users which providers contributed to a merged cluster.
- Allow manual review for ambiguous fuzzy matches.
