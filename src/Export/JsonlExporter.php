<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Utils\Exceptions\ExportError;

class JsonlExporter extends BaseExporter
{
    public function getFileExtension(): string
    {
        return 'jsonl';
    }

    public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $includeRaw = $kwargs['include_raw'] ?? false;
        $indent = $kwargs['indent'] ?? false;

        try {
            $content = '';

            foreach ($documents as $doc) {
                $jsonObj = $this->documentToDict($doc, $includeRaw);

                if ($indent) {
                    $jsonStr = json_encode($jsonObj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    $jsonStr = json_encode($jsonObj, JSON_UNESCAPED_UNICODE);
                }

                $content .= $jsonStr . "\n";
            }

            $this->writeToFile($path, $content);
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write JSONL file: {$e->getMessage()}", 'jsonl');
        }

        return $path;
    }

    public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $mode = $kwargs['mode'] ?? 'representatives';
        $includeRaw = $kwargs['include_raw'] ?? false;

        try {
            $content = '';

            if ($mode === 'representatives') {
                foreach ($clusters as $cluster) {
                    $jsonObj = $this->documentToDict($cluster->representative, $includeRaw);
                    $jsonObj['cluster_metadata'] = $this->clusterMetadataToDict($cluster);
                    $content .= json_encode($jsonObj, JSON_UNESCAPED_UNICODE) . "\n";
                }
            } elseif ($mode === 'all') {
                foreach ($clusters as $cluster) {
                    foreach ($cluster->members as $doc) {
                        $jsonObj = $this->documentToDict($doc, $includeRaw);
                        $content .= json_encode($jsonObj, JSON_UNESCAPED_UNICODE) . "\n";
                    }
                }
            } elseif ($mode === 'clusters') {
                foreach ($clusters as $cluster) {
                    $jsonObj = $this->clusterToDict($cluster, $includeRaw);
                    $content .= json_encode($jsonObj, JSON_UNESCAPED_UNICODE) . "\n";
                }
            }

            $this->writeToFile($path, $content);
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write JSONL file: {$e->getMessage()}", 'jsonl');
        }

        return $path;
    }

    protected function documentToDict(Document $doc, bool $includeRaw = false): array
    {
        $data = [
            'title' => $doc->title,
            'year' => $doc->year,
            'provider' => $doc->provider,
            'provider_id' => $doc->providerId,
            'external_ids' => [
                'doi' => $doc->externalIds->doi,
                'arxiv_id' => $doc->externalIds->arxivId,
                'pubmed_id' => $doc->externalIds->pubmedId,
                'openalex_id' => $doc->externalIds->openalexId,
                's2_id' => $doc->externalIds->s2Id,
            ],
            'abstract' => $doc->abstract,
            'authors' => array_map(
                fn($author) => [
                    'family_name' => $author->familyName,
                    'given_name' => $author->givenName,
                    'orcid' => $author->orcid,
                ],
                $doc->authors
            ),
            'venue' => $doc->venue,
            'url' => $doc->url,
            'language' => $doc->language,
            'cited_by_count' => $doc->citedByCount,
            'query_id' => $doc->queryId,
            'query_text' => $doc->queryText,
            'retrieved_at' => $doc->retrievedAt?->format(\DateTime::ATOM),
            'cluster_id' => $doc->clusterId,
        ];

        if ($includeRaw && $doc->rawData !== null) {
            $data['raw_data'] = $doc->rawData;
        }

        return $data;
    }

    protected function clusterToDict(DocumentCluster $cluster, bool $includeRaw = false): array
    {
        return [
            'cluster_id' => $cluster->clusterId,
            'size' => $cluster->size(),
            'confidence' => $cluster->confidence ?? null,
            'representative' => $this->documentToDict($cluster->representative, $includeRaw),
            'members' => array_map(
                fn($doc) => $this->documentToDict($doc, $includeRaw),
                $cluster->members
            ),
            'all_dois' => $cluster->allDois,
            'all_arxiv_ids' => $cluster->allArxivIds,
            'provider_counts' => $cluster->providerCounts,
        ];
    }

    protected function clusterMetadataToDict(DocumentCluster $cluster): array
    {
        return [
            'cluster_id' => $cluster->clusterId,
            'size' => $cluster->size(),
            'confidence' => $cluster->confidence ?? null,
            'all_dois' => $cluster->allDois,
            'all_arxiv_ids' => $cluster->allArxivIds,
            'provider_counts' => $cluster->providerCounts,
        ];
    }
}
