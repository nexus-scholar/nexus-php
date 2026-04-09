# Citation Snowballing

Snowballing is the process of expanding a literature search by following citations.

## Implementation Guide

Providers that support snowballing must implement `Nexus\Core\SnowballProviderInterface`.

### Backward Snowballing (References)
Implement `getReferencedDocuments(Document $document, int $limit = 50): Generator`.
- **Target**: The `references` list in the original paper metadata.
- **Handling**: Many providers (e.g., Crossref) provide only DOIs in the reference list. You must resolve these DOIs to full `Document` objects.

### Forward Snowballing (Citations)
Implement `getCitingDocuments(Document $document, int $limit = 100): Generator`.
- **Target**: Papers that have cited the given document.
- **Filtering**: If the provider doesn't support direct citation lookup (e.g., PubMed), you must search using the document's DOI in the `references` field of other papers.

## BFS Expansion Strategies
When using snowballing in a `StateGraph`, implement a Breadth-First Search (BFS) to prevent depth-first traps.

```php
$queue = new SplQueue();
$queue->enqueue($initialDocument);
$seen = [$initialDocument->doi => true];
$currentDepth = 0;

while (!$queue->isEmpty() && $currentDepth < $maxDepth) {
    $doc = $queue->dequeue();
    // Fetch citations/references
    foreach ($provider->getCitingDocuments($doc) as $citedDoc) {
        if (!isset($seen[$citedDoc->doi])) {
            $queue->enqueue($citedDoc);
            $seen[$citedDoc->doi] = true;
        }
    }
    $currentDepth++;
}
```

## Cycle Detection
- Always track `seen` DOIs to avoid infinite loops.
- Limit the `maxDepth` (usually 2-3 levels for SLR).
