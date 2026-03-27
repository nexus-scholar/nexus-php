<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Nexus\Config\ConfigLoader;
use Nexus\Core\ProviderFactory;
use Nexus\Core\SnowballService;
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Export\JsonExporter;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Models\Query;
use Nexus\Models\SnowballConfig;
use Symfony\Component\Yaml\Yaml;

echo "=== TomatoMAP SLR - Full Search & Snowballing Demo ===\n\n";

$configData = Yaml::parseFile(__DIR__.'/../../config.yml');
$queriesData = Yaml::parseFile(__DIR__.'/../../queries.yml');

$queries = $queriesData['queries'];
$outputDir = __DIR__;

echo 'Loaded '.count($queries)." queries\n";
echo "Using providers: openalex, s2 (for speed)\n\n";

$config = ConfigLoader::loadDefault();

$allDocuments = [];
$providerStats = ['openalex' => 0, 's2' => 0];

echo "=== Running Search Queries ===\n";
$queryCount = 0;
foreach ($queries as $q) {
    $queryCount++;
    $text = $q['text'];
    $yearMin = $q['year_min'] ?? null;
    $yearMax = $q['year_max'] ?? null;
    $maxResults = min($q['max_results'] ?? 50, 30);
    $queryId = $q['id'] ?? "Q{$queryCount}";

    echo "[$queryCount/".count($queries)."] {$queryId}: ";

    $query = new Query(
        text: $text,
        yearMin: $yearMin,
        yearMax: $yearMax,
        maxResults: $maxResults,
        id: $queryId
    );

    foreach (['openalex', 's2'] as $providerName) {
        try {
            $provider = ProviderFactory::makeFromConfig($providerName, $config);
            $results = iterator_to_array($provider->search($query));

            foreach ($results as $doc) {
                $doc->queryId = $queryId;
                $doc->queryText = $queryId;
                $allDocuments[] = $doc;
            }

            $providerStats[$providerName] += count($results);
        } catch (Exception $e) {
            // Skip errors silently
        }
    }
    echo 'oa:'.$providerStats['openalex'].' s2:'.$providerStats['s2']."\n";
}

echo "\n=== Search Complete ===\n";
echo 'Total documents: '.count($allDocuments)."\n";

$exporter = new JsonExporter($outputDir);
$searchFile = $exporter->exportDocuments($allDocuments, 'search_results', ['include_raw' => false]);
echo "Saved: search_results.json\n";

echo "\n=== Deduplication ===\n";
$dedupConfig = new DeduplicationConfig(
    strategy: DeduplicationStrategyName::CONSERVATIVE,
    fuzzyThreshold: 97,
    maxYearGap: 1
);
$strategy = new ConservativeStrategy($dedupConfig);
$clusters = $strategy->deduplicate($allDocuments);

$dedupedDocs = [];
foreach ($clusters as $cluster) {
    $dedupedDocs[] = $cluster->representative;
}

$dedupFile = $exporter->exportDocuments($dedupedDocs, 'deduped_results', ['include_raw' => false]);
echo 'After deduplication: '.count($dedupedDocs)." unique documents\n";
echo "Saved: deduped_results.json\n";

echo "\n=== Selecting Seeds ===\n";
usort($dedupedDocs, fn ($a, $b) => ($b->citedByCount ?? 0) <=> ($a->citedByCount ?? 0));

$seedsWithIds = array_filter($dedupedDocs, fn ($doc) => $doc->externalIds->openalexId || $doc->externalIds->s2Id
);
$topSeeds = array_slice($seedsWithIds, 0, 5);

echo "Top seeds:\n";
foreach ($topSeeds as $i => $seed) {
    $title = substr($seed->title, 0, 60);
    $cites = $seed->citedByCount ?? 0;
    echo '  '.($i + 1).". [{$cites} cites] {$title}...\n";
}

$seedsFile = $exporter->exportDocuments($topSeeds, 'seeds', ['include_raw' => false]);
echo "Saved: seeds.json\n";

echo "\n=== Snowballing ===\n";
$snowballConfig = new SnowballConfig(
    forward: true,
    backward: true,
    maxCitations: 20,
    maxReferences: 10,
    depth: 1
);

$openalex = ProviderFactory::makeFromConfig('openalex', $config);
$s2 = ProviderFactory::makeFromConfig('s2', $config);
$snowballService = new SnowballService($snowballConfig, $openalex, $s2);

$allExistingDocs = $dedupedDocs;
$snowballResults = [];

foreach ($topSeeds as $i => $seed) {
    if (! $seed->externalIds->openalexId && ! $seed->externalIds->s2Id) {
        continue;
    }

    $title = substr($seed->title, 0, 40);
    echo 'Snowballing seed '.($i + 1)." ({$title}...): ";

    $newDocs = $snowballService->snowball($seed, $allExistingDocs);
    echo count($newDocs)." new\n";

    foreach ($newDocs as $doc) {
        $allExistingDocs[] = $doc;
    }
    $snowballResults = array_merge($snowballResults, $newDocs);
}

$uniqueSnowball = [];
$snowballIds = [];
foreach ($snowballResults as $doc) {
    $key = $doc->externalIds->doi ?? ($doc->externalIds->openalexId ?? ($doc->externalIds->s2Id ?? $doc->title));
    if (! isset($snowballIds[$key])) {
        $snowballIds[$key] = true;
        $uniqueSnowball[] = $doc;
    }
}

echo "\n=== Results ===\n";
echo 'Original search: '.count($allDocuments)." docs\n";
echo 'After dedup: '.count($dedupedDocs)." docs\n";
echo 'New from snowball: '.count($uniqueSnowball)." docs\n";
echo 'Total: '.(count($dedupedDocs) + count($uniqueSnowball))." docs\n";

if (! empty($uniqueSnowball)) {
    $snowballFile = $exporter->exportDocuments($uniqueSnowball, 'snowball_results', ['include_raw' => false]);
    echo "Saved: snowball_results.json\n";
}

echo "\n=== Files Created ===\n";
foreach (glob("{$outputDir}/*.json") as $f) {
    echo basename($f)."\n";
}
