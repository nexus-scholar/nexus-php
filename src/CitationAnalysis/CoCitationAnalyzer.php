<?php

namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class CoCitationAnalyzer
{
    private array $documents = [];

    private array $citingPapersMap = [];

    public function __construct(array $documents)
    {
        $this->documents = $documents;
        $this->citingPapersMap = $this->buildCitingPapersMap();
    }

    public function buildCoCitationMatrix(): array
    {
        $matrix = [];
        $paperIds = array_keys($this->citingPapersMap);
        $n = count($paperIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i; $j < $n; $j++) {
                $paperA = $paperIds[$i];
                $paperB = $paperIds[$j];

                $cocitationCount = count(array_intersect(
                    $this->citingPapersMap[$paperA] ?? [],
                    $this->citingPapersMap[$paperB] ?? []
                ));

                if ($cocitationCount > 0) {
                    $matrix[$paperA][$paperB] = $cocitationCount;
                    if ($i !== $j) {
                        $matrix[$paperB][$paperA] = $cocitationCount;
                    }
                }
            }
        }

        return $matrix;
    }

    public function getCoCitingPapers(string $paperA, string $paperB): array
    {
        $citingA = $this->citingPapersMap[$paperA] ?? [];
        $citingB = $this->citingPapersMap[$paperB] ?? [];

        $commonCiting = array_intersect($citingA, $citingB);

        $result = [];
        foreach ($commonCiting as $citingPaper) {
            $result[$citingPaper] = [$paperA, $paperB];
        }

        return $result;
    }

    public function findSimilarPapers(string $paperId, int $topN = 10): array
    {
        $similarity = $this->getNormalizedSimilarity();

        if (! isset($similarity[$paperId])) {
            return [];
        }

        $paperSimilarity = $similarity[$paperId];
        arsort($paperSimilarity);

        return array_slice($paperSimilarity, 0, $topN, preserve_keys: true);
    }

    public function buildSimilarityGraph(float $minThreshold = 0.1): Graph
    {
        $normalized = $this->getNormalizedSimilarity();
        $graph = new Graph(directed: false);

        foreach ($this->documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        foreach ($normalized as $paperA => $neighbors) {
            foreach ($neighbors as $paperB => $score) {
                if ($score >= $minThreshold && strcmp($paperA, $paperB) < 0) {
                    $graph->addEdge($paperA, $paperB, ['weight' => $score]);
                }
            }
        }

        return $graph;
    }

    public function getNormalizedSimilarity(): array
    {
        $normalized = [];
        $paperIds = array_keys($this->citingPapersMap);
        $n = count($paperIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $paperA = $paperIds[$i];
                $paperB = $paperIds[$j];

                $citingA = $this->citingPapersMap[$paperA] ?? [];
                $citingB = $this->citingPapersMap[$paperB] ?? [];

                $intersection = array_intersect($citingA, $citingB);
                $union = array_unique(array_merge($citingA, $citingB));

                $cocitationCount = count($intersection);
                $unionCount = count($union);

                if ($unionCount > 0) {
                    $score = $cocitationCount / $unionCount;
                    $normalized[$paperA][$paperB] = $score;
                    $normalized[$paperB][$paperA] = $score;
                } else {
                    $normalized[$paperA][$paperB] = 0.0;
                    $normalized[$paperB][$paperA] = 0.0;
                }
            }
        }

        return $normalized;
    }

    private function buildCitingPapersMap(): array
    {
        $map = [];

        foreach ($this->documents as $document) {
            $nodeId = $this->getNodeId($document);
            $citingPapers = [];

            if ($document->rawData && isset($document->rawData['citing_papers'])) {
                $citingPapers = $document->rawData['citing_papers'];
            }

            if ($document->rawData && isset($document->rawData['cited_by'])) {
                $citingPapers = array_merge($citingPapers, $document->rawData['cited_by']);
            }

            $map[$nodeId] = array_unique($citingPapers);
        }

        return $map;
    }

    private function getNodeId(Document $document): string
    {
        if ($document->externalIds?->doi) {
            return 'doi:'.$document->externalIds->doi;
        }

        return 'id:'.$document->providerId;
    }

    private function documentToNodeAttributes(Document $document): array
    {
        return [
            'id' => $this->getNodeId($document),
            'label' => $this->truncateLabel($document->title),
            'title' => $document->title,
            'year' => $document->year,
            'citations' => $document->citedByCount ?? 0,
            'doi' => $document->externalIds?->doi,
            'venue' => $document->venue,
            'query_id' => $document->queryId,
        ];
    }

    private function truncateLabel(string $title, int $maxLength = 100): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }
}
