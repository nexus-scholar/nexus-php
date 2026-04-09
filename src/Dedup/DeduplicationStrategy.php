<?php

namespace Nexus\Dedup;

use Nexus\Models\DeduplicationConfig;
use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;

abstract class DeduplicationStrategy
{
    public function __construct(protected DeduplicationConfig $config) {}

    /**
     * Get the priority of a provider.
     */
    protected function getProviderPriority(string $provider): int
    {
        return $this->config->providerPriority[$provider] ?? 99;
    }

    /**
     * @param  Document[]  $documents
     * @return DocumentCluster[]
     */
    abstract public function deduplicate(array $documents, ?callable $progressCallback = null): array;

    public static function normalizeTitle(?string $title): string
    {
        if (! $title) {
            return '';
        }

        // Basic normalization: lowercase, remove non-alphanumeric except spaces
        $title = mb_strtolower($title, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', $title);
        $title = preg_replace('/[^\w\s\-]/u', '', $title);

        return trim($title);
    }

    public static function normalizeDoi(?string $doi): string
    {
        if (! $doi) {
            return '';
        }
        $doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $doi);
        $doi = preg_replace('/^doi:\s*/i', '', $doi);

        return strtolower(trim($doi));
    }

    public function createCluster(int $clusterId, array $documents, ?Document $representative = null): DocumentCluster
    {
        if (empty($documents)) {
            throw new \InvalidArgumentException('Cannot create cluster with no documents');
        }

        if ($representative === null) {
            $representative = $this->fuseDocuments($documents);
        }

        $allDois = [];
        $allArxivIds = [];
        $providerCounts = [];

        foreach ($documents as $doc) {
            if ($doc->externalIds->doi) {
                $normDoi = self::normalizeDoi($doc->externalIds->doi);
                if ($normDoi && ! in_array($normDoi, $allDois)) {
                    $allDois[] = $normDoi;
                }
            }
            if ($doc->externalIds->arxivId) {
                $arxivId = strtolower(trim($doc->externalIds->arxivId));
                if ($arxivId && ! in_array($arxivId, $allArxivIds)) {
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

    protected function fuseDocuments(array $documents): Document
    {
        if (count($documents) === 1) {
            return $documents[0];
        }

        $sorted = $documents;
        usort($sorted, function (Document $a, Document $b) {
            $scoreA = $this->getProviderPriority($a->provider);
            $scoreB = $this->getProviderPriority($b->provider);

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return ($b->citedByCount ?? 0) <=> ($a->citedByCount ?? 0);
        });

        $best = $sorted[0];
        // Clone the document by manual mapping to avoid references
        $fused = new Document(
            title: $best->title,
            year: $best->year,
            provider: $best->provider,
            providerId: $best->providerId,
            externalIds: clone $best->externalIds,
            abstract: $best->abstract,
            authors: $best->authors,
            venue: $best->venue,
            url: $best->url,
            language: $best->language,
            citedByCount: $best->citedByCount,
            queryId: $best->queryId,
            queryText: $best->queryText,
            retrievedAt: $best->retrievedAt,
            clusterId: $best->clusterId,
            rawData: $best->rawData
        );

        // Fuse Abstract (longest valid)
        $isValidAbstract = fn (?string $text) => $text && mb_strlen(trim($text)) > 20 && ! in_array(strtolower(trim($text)), ['no abstract available', 'abstract not available']);

        foreach ($documents as $doc) {
            if ($isValidAbstract($doc->abstract)) {
                if (! $isValidAbstract($fused->abstract) || mb_strlen($doc->abstract) > mb_strlen($fused->abstract)) {
                    $fused->abstract = $doc->abstract;
                }
            }
        }

        // Fuse IDs
        foreach ($documents as $doc) {
            if (! $fused->externalIds->doi) {
                $fused->externalIds->doi = $doc->externalIds->doi;
            }
            if (! $fused->externalIds->arxivId) {
                $fused->externalIds->arxivId = $doc->externalIds->arxivId;
            }
            if (! $fused->externalIds->pubmedId) {
                $fused->externalIds->pubmedId = $doc->externalIds->pubmedId;
            }
            if (! $fused->externalIds->openalexId) {
                $fused->externalIds->openalexId = $doc->externalIds->openalexId;
            }
            if (! $fused->externalIds->s2Id) {
                $fused->externalIds->s2Id = $doc->externalIds->s2Id;
            }
        }

        // Fuse Citation count (max)
        foreach ($documents as $doc) {
            $fused->citedByCount = max($fused->citedByCount ?? 0, $doc->citedByCount ?? 0);
        }

        if (! $fused->year) {
            foreach ($documents as $doc) {
                if ($doc->year) {
                    $fused->year = $doc->year;
                    break;
                }
            }
        }

        if ($fused->externalIds->doi && ! str_contains($fused->url ?? '', 'doi.org')) {
            $fused->url = "https://doi.org/{$fused->externalIds->doi}";
        }

        return $fused;
    }
}
