<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Retrieval\PDFFetcher;
use Nexus\Config\ConfigLoader;

echo "=== PDF Retrieval Demo ===\n\n";

$config = ConfigLoader::loadDefault();
$pdfsDir = __DIR__ . '/pdfs';

if (!is_dir($pdfsDir)) {
    mkdir($pdfsDir, 0755, true);
}

$fetcher = new PDFFetcher($pdfsDir, $config->providers['openalex']->mailto ?? null);

$inputFile = __DIR__ . '/search_results.json';
$data = json_decode(file_get_contents($inputFile), true);
echo "Loaded " . count($data) . " documents from search_results.json\n\n";

$arxivDocs = [];
foreach ($data as $item) {
    if (!empty($item['external_ids']['arxiv_id'])) {
        $externalIds = new ExternalIds(
            doi: $item['external_ids']['doi'] ?? null,
            arxivId: $item['external_ids']['arxiv_id'] ?? null,
            openalexId: $item['external_ids']['openalex_id'] ?? null
        );

        $doc = new Document(
            title: $item['title'],
            provider: $item['provider'],
            providerId: $item['provider_id'],
            externalIds: $externalIds,
            citedByCount: $item['cited_by_count'] ?? null
        );

        $arxivDocs[] = $doc;
    }
}

echo "Found " . count($arxivDocs) . " documents with arXiv IDs\n\n";

echo "=== Checking PDF Availability ===\n";
$pdfAvailable = 0;
$downloaded = 0;

foreach ($arxivDocs as $i => $doc) {
    $title = substr($doc->title, 0, 50);
    $arxivId = $doc->externalIds->arxivId;
    
    $url = $fetcher->getPdfUrl($doc);
    
    if ($url) {
        $pdfAvailable++;
        echo "[{$pdfAvailable}] arXiv:{$arxivId} - {$title}...\n";
        
        $path = $fetcher->fetch($doc);
        if ($path) {
            $downloaded++;
            echo "        Downloaded: " . basename($path) . "\n";
        }
    }
    
    if ($pdfAvailable >= 10) {
        break;
    }
}

echo "\n=== Summary ===\n";
echo "arXiv documents with available PDFs: {$pdfAvailable}\n";
echo "Downloaded: {$downloaded}\n";
echo "Output directory: {$pdfsDir}\n";

$files = glob("{$pdfsDir}/*.pdf");
if (!empty($files)) {
    echo "\n=== Downloaded Files ===\n";
    foreach ($files as $file) {
        $size = round(filesize($file) / 1024 / 1024, 2);
        echo basename($file) . " ({$size} MB)\n";
    }
}
