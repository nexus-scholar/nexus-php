<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Retrieval\PDFFetcher;
use Nexus\Retrieval\Sources\ArxivSource;
use Nexus\Retrieval\Sources\OpenAlexSource;

echo "=== PDF Retrieval - Maximum Yield (Consolidated) ===\n\n";

$pdfsDir = __DIR__.'/pdfs';
if (! is_dir($pdfsDir)) {
    mkdir($pdfsDir, 0755, true);
}

$email = 'bekhouche.mouadh@univ-oeb.dz';
$fetcher = new PDFFetcher($pdfsDir, $email);

$inputFile = __DIR__.'/deduped_results.json';
$data = json_decode(file_get_contents($inputFile), true);
echo 'Loaded '.count($data)." documents\n\n";

$documents = [];
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
}

$arxivSource = new ArxivSource($email);
$openalexSource = new OpenAlexSource($email);

$limit = 300;
echo "=== Checking first {$limit} documents ===\n\n";

$stats = ['arxiv' => 0, 'openalex' => 0, 'none' => 0];

for ($i = 0; $i < min($limit, count($documents)); $i++) {
    $doc = $documents[$i];

    $hasArxiv = $doc->externalIds->arxivId ? true : false;
    $hasOpenalex = false;

    if ($doc->externalIds->doi) {
        $urls = $openalexSource->getPdfUrls($doc);
        $hasOpenalex = ! empty($urls);
    }

    if ($hasArxiv) {
        $stats['arxiv']++;
    } elseif ($hasOpenalex) {
        $stats['openalex']++;
    } else {
        $stats['none']++;
    }

    $title = substr($doc->title, 0, 40);
    echo "[{$i}] {$title}...\n";
    echo '    arXiv: '.($hasArxiv ? '✓' : '✗').' | OpenAlex OA: '.($hasOpenalex ? '✓' : '✗')."\n";
}

echo "\n=== Summary ===\n";
echo "arXiv: {$stats['arxiv']}\n";
echo "OpenAlex: {$stats['openalex']}\n";
echo "Neither: {$stats['none']}\n\n";

echo "=== Downloading ===\n";
$downloaded = 0;

foreach (array_slice($documents, 0, $limit) as $doc) {
    $path = $fetcher->fetch($doc);
    if ($path) {
        $downloaded++;
        echo '✓ '.basename($path)."\n";
    }
}

echo "\nTotal: {$downloaded} PDFs\n";
