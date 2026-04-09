# Scholarly Deduplication Strategies

Deduplication is a critical step in the Systematic Literature Review (SLR) pipeline to ensure each paper is only analyzed once.

## Conservative Strategy (DOI-Only)
The `Nexus\Dedup\ConservativeStrategy` identifies duplicates solely by DOI.
- **Rules**:
    - DOIs must be normalized (stripped of `https://doi.org/` prefix) before comparison.
    - Case-insensitive matching.
- **When to use**: Initial screening phase or when data quality is extremely high.

## Fuzzy Strategy (Title-Based)
The `Nexus\Dedup\FuzzyStrategy` handles documents with missing or malformed DOIs.

### Title Normalization Process
1.  **Lowercase**: Convert the entire title to lowercase.
2.  **Strip Punctuation**: Remove all special characters and multiple spaces.
3.  **Stemming (Optional)**: Reduce words to their root form.

### Similarity Metrics
Use the `Nexus\Utils\StringSimilarity` class to compute scores.
- **Levenshtein Distance**: Good for catching typos.
- **Jaccard Index**: Better for token-level matching (reordered words).

### Recommended Thresholds
- **High Confidence**: 0.9+ similarity score (treat as duplicate).
- **Manual Review**: 0.75 - 0.9 (flag for human review).

## Deduplication Workflow
```php
$strategy = DeduplicationStrategy::create(DeduplicationStrategyName::CONSERVATIVE);
$deduplicated = $strategy->deduplicate($documents);
```
- **Note**: The input array of documents is processed in order. The first occurrence of a document is kept, subsequent duplicates are discarded.
