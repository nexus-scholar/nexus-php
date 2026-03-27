<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Utils\Exceptions\ExportError;
use SplFileObject;

class CsvExporter extends BaseExporter
{
    private array $usedKeys = [];

    public function getFileExtension(): string
    {
        return 'csv';
    }

    public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $includeRaw = $kwargs['include_raw'] ?? false;

        try {
            $file = new SplFileObject($path, 'w');

            if (!empty($documents)) {
                $fieldnames = $this->getFieldnames($documents[0], $includeRaw);
                $file->fputcsv($fieldnames, ',', '"', '\\');

                foreach ($documents as $doc) {
                    $row = $this->documentToRow($doc, $includeRaw);
                    $file->fputcsv(array_values($row), ',', '"', '\\');
                }
            } else {
                $fieldnames = $this->getDefaultFieldnames();
                $file->fputcsv($fieldnames, ',', '"', '\\');
            }
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write CSV file: {$e->getMessage()}", 'csv');
        }

        return $path;
    }

    public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $mode = $kwargs['mode'] ?? 'representatives';

        try {
            $file = new SplFileObject($path, 'w');

            if ($mode === 'representatives') {
                $this->writeClusterRepresentatives($file, $clusters);
            } else {
                $this->writeAllClusterMembers($file, $clusters);
            }
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write CSV file: {$e->getMessage()}", 'csv');
        }

        return $path;
    }

    private function writeClusterRepresentatives(SplFileObject $file, array $clusters): void
    {
        if (empty($clusters)) {
            $fieldnames = array_merge($this->getDefaultFieldnames(), $this->getClusterFieldnames());
            $file->fputcsv($fieldnames, ',', '"', '\\');

            return;
        }

        $baseFieldnames = $this->getFieldnames($clusters[0]->representative, false);
        $clusterFieldnames = $this->getClusterFieldnames();
        $fieldnames = array_merge($baseFieldnames, $clusterFieldnames);

        $file->fputcsv($fieldnames, ',', '"', '\\');

        foreach ($clusters as $cluster) {
            $row = $this->documentToRow($cluster->representative, false);
            $clusterRow = $this->clusterToRow($cluster);
            $file->fputcsv(array_merge(array_values($row), array_values($clusterRow)), ',', '"', '\\');
        }
    }

    private function writeAllClusterMembers(SplFileObject $file, array $clusters): void
    {
        if (empty($clusters)) {
            $file->fputcsv(array_merge($this->getDefaultFieldnames(), ['cluster_id']), ',', '"', '\\');

            return;
        }

        $allDocs = [];
        foreach ($clusters as $cluster) {
            foreach ($cluster->members as $member) {
                $allDocs[] = $member;
            }
        }

        if (empty($allDocs)) {
            return;
        }

        $fieldnames = $this->getFieldnames($allDocs[0], false);
        $file->fputcsv($fieldnames, ',', '"', '\\');

        foreach ($allDocs as $doc) {
            $row = $this->documentToRow($doc, false);
            $file->fputcsv(array_values($row), ',', '"', '\\');
        }
    }

    private function documentToRow(Document $doc, bool $includeRaw = false): array
    {
        $row = [
            'title' => $doc->title ?? '',
            'year' => $doc->year ?? '',
            'provider' => $doc->provider ?? '',
            'provider_id' => $doc->providerId ?? '',
            'abstract' => $doc->abstract ?? '',
            'venue' => $doc->venue ?? '',
            'url' => $doc->url ?? '',
            'language' => $doc->language ?? '',
            'cited_by_count' => $doc->citedByCount ?? '',
            'query_id' => $doc->queryId ?? '',
            'query_text' => $doc->queryText ?? '',
            'retrieved_at' => $doc->retrievedAt?->format(\DateTime::ATOM) ?? '',
            'cluster_id' => $doc->clusterId ?? '',
        ];

        $row['authors'] = $this->formatAuthors($doc->authors);
        $row['author_count'] = count($doc->authors);

        $row['doi'] = $doc->externalIds->doi ?? '';
        $row['arxiv_id'] = $doc->externalIds->arxivId ?? '';
        $row['pubmed_id'] = $doc->externalIds->pubmedId ?? '';
        $row['openalex_id'] = $doc->externalIds->openalexId ?? '';
        $row['s2_id'] = $doc->externalIds->s2Id ?? '';

        if ($includeRaw && $doc->rawData !== null) {
            $row['raw_data'] = json_encode($doc->rawData);
        }

        return $row;
    }

    private function clusterToRow(DocumentCluster $cluster): array
    {
        return [
            'cluster_size' => $cluster->size(),
            'cluster_confidence' => $cluster->confidence ?? '',
            'cluster_dois' => implode('; ', $cluster->allDois),
            'cluster_arxiv_ids' => implode('; ', $cluster->allArxivIds),
            'cluster_providers' => implode('; ', array_map(
                fn ($k, $v) => "{$k}({$v})",
                array_keys($cluster->providerCounts),
                array_values($cluster->providerCounts)
            )),
        ];
    }

    private function formatAuthors(array $authors): string
    {
        if (empty($authors)) {
            return '';
        }

        $authorStrs = [];
        foreach ($authors as $author) {
            $authorStrs[] = $author->getFullName();
        }

        return implode('; ', $authorStrs);
    }

    private function getFieldnames(Document $doc, bool $includeRaw = false): array
    {
        $fieldnames = [
            'title', 'year', 'authors', 'author_count', 'venue', 'abstract',
            'provider', 'provider_id', 'doi', 'arxiv_id', 'pubmed_id',
            'openalex_id', 's2_id', 'url', 'language', 'cited_by_count',
            'query_id', 'query_text', 'retrieved_at', 'cluster_id',
        ];

        if ($includeRaw) {
            $fieldnames[] = 'raw_data';
        }

        return $fieldnames;
    }

    private function getDefaultFieldnames(): array
    {
        return [
            'title', 'year', 'authors', 'author_count', 'venue', 'abstract',
            'provider', 'provider_id', 'doi', 'arxiv_id', 'pubmed_id',
            'openalex_id', 's2_id', 'url', 'language', 'cited_by_count',
            'query_id', 'query_text', 'retrieved_at', 'cluster_id',
        ];
    }

    private function getClusterFieldnames(): array
    {
        return [
            'cluster_size', 'cluster_confidence', 'cluster_dois',
            'cluster_arxiv_ids', 'cluster_providers',
        ];
    }
}
