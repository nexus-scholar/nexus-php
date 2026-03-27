<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Retrieval\PDFFetcher;
use Nexus\Retrieval\Sources\ArxivSource;
use Nexus\Retrieval\Sources\DirectSource;
use Nexus\Retrieval\Sources\SemanticScholarSource;
use Nexus\Retrieval\Sources\UnpaywallSource;

echo "=== PDF Retrieval - Maximum Yield Demo ===\n\n";

$pdfsDir = __DIR__ . '/pdfs';
if (!is_dir($pdfsDir)) {
    mkdir($pdfsDir, 0755, true);
}

$fetcher = new PDFFetcher($pdfsDir, 'bekhouche.mouadh@univ-oeb.dz');

$inputFile = __DIR__ . '/deduped_results.json';
$data = json_decode(file_get_contents($inputFile), true);
echo "Loaded " . count($data) . " documents (deduped)\n\n";

$documents = [];
$stats = ['arxiv' => 0, 'doi' => 0, 'pubmed' => 0, 'none' => 0];

foreach ($data as $item) {
    $externalIds = new ExternalIds(
        doi: $item['external_ids']['doi'] ?? null,
        arxivId: $item['external_ids']['arxiv_id'] ?? null,
        pubmedId: $item['external_ids']['pubmed_id'] ?? null,
        openalexId: $item['external_ids']['openalex_id'] ?? null,
        s2Id: $item['external_ids']['s2_id'] ?? null
    );

    $doc = new Document(
        title: $item['title'],
        year: $item['year'] ?? null,
        provider: $item['provider'],
        providerId: $item['provider_id'],
        externalIds: $externalIds,
        url: $item['url'] ?? null,
        citedByCount: $item['cited_by_count'] ?? null
    );

    $documents[] = $doc;

    if ($externalIds->arxivId) $stats['arxiv']++;
    elseif ($externalIds->doi) $stats['doi']++;
    elseif ($externalIds->pubmedId) $stats['pubmed']++;
    else $stats['none']++;
}

echo "Documents breakdown:\n";
echo "  arXiv: {$stats['arxiv']}\n";
echo "  DOI only: {$stats['doi']}\n";
echo "  PubMed: {$stats['pubmed']}\n";
echo "  No IDs: {$stats['none']}\n\n";

$arxivSource = new ArxivSource();
$unpaywallSource = new UnpaywallSource('bekhouche.mouadh@univ-oeb.dz');

echo "=== Checking Sources ===\n\n";

$results = [
    'arxiv' => 0,
    'unpaywall' => 0,
    'direct' => 0,
];

foreach ($documents as $doc) {
    if ($doc->externalIds->arxivId) {
        if ($arxivSource->getPdfUrl($doc)) {
            $results['arxiv']++;
        }
    }
    
    if ($doc->externalIds->doi) {
        if ($unpaywallSource->getPdfUrl($doc)) {
            $results['unpaywall']++;
        }
    }
}

echo "arXiv available: {$results['arxiv']}\n";
echo "Unpaywall available: {$results['unpaywall']}\n\n";

echo "=== Downloading arXiv PDFs ===\n";
$downloaded = 0;
foreach ($documents as $doc) {
    if ($doc->externalIds->arxivId) {
        $path = $fetcher->fetch($doc);
        if ($path) {
            $downloaded++;
            echo "✓ " . basename($path) . "\n";
        }
    }
    if ($downloaded >= 50) break;
}

echo "\n=== Downloading from DOI (Unpaywall) ===\n";
$doiDownloaded = 0;
foreach ($documents as $doc) {
    if (!$doc->externalIds->arxivId && $doc->externalIds->doi) {
        $path = $fetcher->fetch($doc);
        if ($path) {
            $doiDownloaded++;
            echo "✓ " . basename($path) . "\n";
        }
    }
    if ($doiDownloaded >= 20) break;
}

echo "\n=== Summary ===\n";
echo "arXiv PDFs: {$downloaded}\n";
echo "DOI PDFs (Unpaywall): {$doiDownloaded}\n";

$files = glob("{$pdfsDir}/*.pdf");
echo "\nTotal: " . count($files) . " PDFs\n";

$totalSize = array_sum(array_map('filesize', $files));
echo "Size: " . round($totalSize / 1024 / 1024, 2) . " MB\n";
