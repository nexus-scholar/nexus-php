<?php

namespace Nexus\Laravel\Commands;

use Illuminate\Console\Command;
use Nexus\Laravel\NexusSearcher;
use Nexus\Models\Query;

class SearchCommand extends Command
{
    protected $signature = 'nexus:search
                            {query : The search query text}
                            {--providers= : Comma-separated list of providers (openalex,crossref,arxiv,s2,pubmed,doaj,ieee)}
                            {--max-results=50 : Maximum number of results per provider}
                            {--year-min= : Minimum publication year}
                            {--year-max= : Maximum publication year}
                            {--no-cache : Disable caching}
                            {--format=table : Output format (table, json, csv)}';

    protected $description = 'Search academic databases using Nexus';

    public function handle(NexusSearcher $searcher): int
    {
        $queryText = $this->argument('query');
        $providers = $this->option('providers') ? explode(',', $this->option('providers')) : null;
        $maxResults = (int) $this->option('max-results');
        $yearMin = $this->option('year-min') ? (int) $this->option('year-min') : null;
        $yearMax = $this->option('year-max') ? (int) $this->option('year-max') : null;
        $useCache = ! $this->option('no-cache');
        $format = $this->option('format');

        $query = new Query(
            text: $queryText,
            maxResults: $maxResults,
            yearMin: $yearMin,
            yearMax: $yearMax
        );

        $this->info("Searching for: {$queryText}");
        if ($providers) {
            $this->info('Providers: '.implode(', ', $providers));
        }
        $this->newLine();

        try {
            $startTime = microtime(true);
            $results = $searcher->search($query, $providers, $useCache);
            $duration = microtime(true) - $startTime;

            $this->info("Found {$this->countResults($results)} results in ".round($duration, 2).'s');

            $this->displayResults($results, $format);
        } catch (\Throwable $e) {
            $this->error('Search failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function countResults(array $results): int
    {
        return count($results);
    }

    private function displayResults(array $results, string $format): void
    {
        if (empty($results)) {
            $this->warn('No results found.');

            return;
        }

        match ($format) {
            'json' => $this->displayJson($results),
            'csv' => $this->displayCsv($results),
            default => $this->displayTable($results),
        };
    }

    private function displayTable(array $results): void
    {
        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                $result->provider ?? 'unknown',
                substr($result->title ?? '', 0, 60).(strlen($result->title ?? '') > 60 ? '...' : ''),
                $result->year ?? 'N/A',
                ($result->authors[0]->familyName ?? null) ?? 'N/A',
            ];
        }

        $this->table(['Provider', 'Title', 'Year', 'Author'], $rows);
    }

    private function displayJson(array $results): void
    {
        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'title' => $result->title,
                'year' => $result->year,
                'provider' => $result->provider,
                'authors' => array_map(fn ($a) => $a->familyName ?? '', $result->authors ?? []),
                'doi' => $result->externalIds->doi ?? null,
            ];
        }
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function displayCsv(array $results): void
    {
        $this->line('title,year,provider,authors,doi');
        foreach ($results as $result) {
            $authors = implode(';', array_map(fn ($a) => $a->familyName ?? '', $result->authors ?? []));
            $doi = $result->externalIds->doi ?? '';
            $this->line("\"{$result->title}\",{$result->year},{$result->provider},\"{$authors}\",{$doi}");
        }
    }
}
