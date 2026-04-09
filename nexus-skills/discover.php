#!/usr/bin/env php
<?php

/**
 * nexus-skills/discover.php
 *
 * Standalone CLI skill discovery for nexus-php.
 * Runs automatically after `composer install` (post-install-cmd).
 *
 * Usage:
 *   php nexus-skills/discover.php              # human-readable list
 *   php nexus-skills/discover.php --json       # machine-readable JSON
 *   php nexus-skills/discover.php --skill=<id> # detail for one skill
 */

declare(strict_types=1);

$format = 'human';
$skillFilter = null;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--json') {
        $format = 'json';
    }
    if (str_starts_with($arg, '--skill=')) {
        $skillFilter = substr($arg, 8);
    }
}

$manifestPath = __DIR__.'/skills.json';

if (! file_exists($manifestPath)) {
    fwrite(STDERR, "Error: skills.json not found at {$manifestPath}\n");
    exit(1);
}

$manifest = json_decode(file_get_contents($manifestPath), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, 'Error: Invalid skills.json — '.json_last_error_msg()."\n");
    exit(1);
}

if ($skillFilter !== null) {
    $filtered = array_values(array_filter(
        $manifest['skills'],
        static fn ($s) => $s['id'] === $skillFilter
    ));
    if (empty($filtered)) {
        fwrite(STDERR, "No skill found with id '{$skillFilter}'\n");
        exit(1);
    }
    $manifest['skills'] = $filtered;
}

if ($format === 'json') {
    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

// ── Human-readable output ────────────────────────────────────────────────────

$pkg = $manifest['package'];
$version = $manifest['version'];
$php = $manifest['php'];
$count = count($manifest['skills']);

$line = str_repeat('\u2500', 62);
$box = [
    "\u250c".str_repeat('\u2550', 60)."\u2510",
    "\u2551".str_pad('  nexus-php \u2014 Agent Skills Registry', 60)."\u2551",
    "\u2514".str_repeat('\u2550', 60)."\u255d",
];

echo "\n";
foreach ($box as $row) {
    echo $row."\n";
}
echo "\n";
echo "  Package : {$pkg} v{$version}\n";
echo "  PHP     : {$php}\n";
echo "  Skills  : {$count} available\n";
echo "\n".$line."\n";

foreach ($manifest['skills'] as $skill) {
    $id = str_pad($skill['id'], 20);
    $name = $skill['name'];
    $desc = wordwrap($skill['description'], 55, "\n".str_repeat(' ', 26), true);

    echo "\n  \u25ba {$id}  {$name}\n";
    echo '    '.str_repeat(' ', 22).$desc."\n";
    echo '    Entry : '.($skill['entry'] ?? 'N/A')."\n";

    if (! empty($skill['command'])) {
        echo '    CLI   : '.$skill['command']."\n";
    }
    if (! empty($skill['docs'])) {
        echo '    Docs  : '.$skill['docs']."\n";
    }
}

echo "\n".$line."\n";
echo "\n  --json for machine-readable output";
echo " | --skill=<id> for detail\n\n";
