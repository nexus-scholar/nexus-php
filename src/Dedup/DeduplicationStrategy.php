<?php

namespace Nexus\Dedup;

use Nexus\Models\DeduplicationConfig;
use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Models\ExternalIds;

abstract class DeduplicationStrategy
{
    public function __construct(protected DeduplicationConfig $config) {}

    /**
     * @param Document[] $documents
     * @return DocumentCluster[]
     */
    abstract public function deduplicate(array $documents, ?callable $progressCallback = null): array;

    public static function normalizeTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        // Basic normalization: lowercase, remove non-alphanumeric except spaces
        $title = mb_strtolower($title, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', $title);
        $title = preg_replace('/[^\w\s]/u', '', $title);

        return trim($title);
    }

    public static function normalizeDoi(?string $doi): string
    {
        if (!$doi) {
            return '';
        }
        $doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $doi);
        $doi = preg_replace('/^doi:\s*/i', '', $doi);
        return strtolower(trim($doi));
    }

    public static function createCluster(int $clusterId, array $documents, ?Document $representative = null): DocumentCluster
    {
        if (empty($documents)) {
            throw new \InvalidArgumentException("Cannot create cluster with no documents");
        }

        if ($representative === null) {
            $representative = self::fuseDocuments($documents);
        }

        $allDois = [];
        $allArxivIds = [];
        $providerCounts = [];

        foreach ($documents as $doc) {
            if ($doc->externalIds->doi) {
                $normDoi = self::normalizeDoi($doc->externalIds->doi);
                if ($normDoi && !in_array($normDoi, $allDois)) {
                    $allDois[] = $normDoi;
                }
            }
            if ($doc->externalIds->arxivId) {
                $arxivId = strtolower(trim($doc->externalIds->arxivId));
                if ($arxivId && !in_array($arxivId, $allArxivIds)) {
                    $allArxivIds[] = $arxivId;
                }
            }
            $providerCounts[$doc->provider] = ($providerCounts[$doc->provider] ?? 0) + 1;
        }

        return new DocumentCluster(
            clusterId: $clusterId,
            representative: $representative,
            members: $documents,
            allDois: $allDois,
            allArxivIds: $allArxivIds,
            providerCounts: $providerCounts
        );
    }

    public static function fuseDocuments(array $documents): Document
    {
        $providerPriority = [
            'crossref' => 5,
            'pubmed' => 4,
            'openalex' => 3,
            'semantic_scholar' => 2,
            's2' => 2,
            'arxiv' => 1,
        ];

        usort($documents, function (Document $a, Document $b) use ($providerPriority) {
            $pA = $providerPriority[strtolower($a->provider)] ?? 0;
            $pB = $providerPriority[strtolower($b->provider)] ?? 0;

            if ($pA !== $pB) {
                return $pB <=> $pA;
            }

            return ($b->citedByCount ?? 0) <=> ($a->citedByCount ?? 0);
        });

        $base = $documents[0];
        // Clone the document by manual mapping to avoid references
        $fused = new Document(
            title: $base->title,
            year: $base->year,
            provider: $base->provider,
            providerId: $base->providerId,
            externalIds: clone $base->externalIds,
            abstract: $base->abstract,
            authors: $base->authors,
            venue: $base->venue,
            url: $base->url,
            language: $base->language,
            citedByCount: $base->citedByCount,
            queryId: $base->queryId,
            queryText: $base->queryText,
            retrievedAt: $base->retrievedAt,
            clusterId: $base->clusterId,
            rawData: $base->rawData
        );

        // Fuse Abstract (longest valid)
        $isValidAbstract = fn(?string $text) => $text && mb_strlen(trim($text)) > 20 && !in_array(strtolower(trim($text)), ['no abstract available', 'abstract not available']);
        
        foreach ($documents as $doc) {
            if ($isValidAbstract($doc->abstract)) {
                if (!$isValidAbstract($fused->abstract) || mb_strlen($doc->abstract) > mb_strlen($fused->abstract)) {
                    $fused->abstract = $doc->abstract;
                }
            }
        }

        // Fuse IDs
        foreach ($documents as $doc) {
            if (!$fused->externalIds->doi) $fused->externalIds->doi = $doc->externalIds->doi;
            if (!$fused->externalIds->arxivId) $fused->externalIds->arxivId = $doc->externalIds->arxivId;
            if (!$fused->externalIds->pubmedId) $fused->externalIds->pubmedId = $doc->externalIds->pubmedId;
            if (!$fused->externalIds->openalexId) $fused->externalIds->openalexId = $doc->externalIds->openalexId;
            if (!$fused->externalIds->s2Id) $fused->externalIds->s2Id = $doc->externalIds->s2Id;
        }

        // Fuse Citation count (max)
        foreach ($documents as $doc) {
            $fused->citedByCount = max($fused->citedByCount ?? 0, $doc->citedByCount ?? 0);
        }

        if (!$fused->year) {
            foreach ($documents as $doc) {
                if ($doc->year) {
                    $fused->year = $doc->year;
                    break;
                }
            }
        }

        if ($fused->externalIds->doi && !str_contains($fused->url ?? '', 'doi.org')) {
            $fused->url = "https://doi.org/{$fused->externalIds->doi}";
        }

        return $fused;
    }
}
