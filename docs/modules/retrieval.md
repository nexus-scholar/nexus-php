# Retrieval Module

The Retrieval module is responsible for finding full-text PDFs and related downloadable paper assets using multiple fallback sources.

## Files

- `src/Retrieval/PDFFetcher.php`
- `src/Retrieval/PDFSourceInterface.php`
- `src/Retrieval/Sources/ArxivSource.php`
- `src/Retrieval/Sources/OpenAlexSource.php`
- `src/Retrieval/Sources/SemanticScholarSource.php`
- `src/Retrieval/Sources/DirectSource.php`
- `src/Retrieval/Sources/BaseSource.php`

## What it does

- Attempts to locate a downloadable PDF for a document.
- Uses multiple sources in fallback order.
- Supports open-access-first retrieval workflows.

## Example usage

```php
use Nexus\Retrieval\PDFFetcher;

$fetcher = new PDFFetcher();

$pdf = $fetcher->fetch($document);
if ($pdf !== null) {
    file_put_contents('paper.pdf', $pdf->contents);
}
```

## Retrieval flow

1. Accept a normalized `Document`.
2. Inspect identifiers and URLs.
3. Query configured retrieval sources.
4. Return the first successful match.
5. Optionally persist metadata and binary content.

## Source types

- **ArxivSource** for arXiv-hosted papers.
- **OpenAlexSource** for metadata-driven open-access links.
- **SemanticScholarSource** for additional OA or mirrored links.
- **DirectSource** for direct URLs already present in metadata.

## Best practices

- Cache successful retrievals.
- Respect publisher terms and robots policies.
- Separate metadata retrieval from binary file storage.
- Prefer OA routes before adding custom scraping logic.
