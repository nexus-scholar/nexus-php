<?php

namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class BibliographicCoupling
{
    private array $documents = [];
    private array $referencesMap = [];

    public function __construct(array $documents)
    {
        $this->documents = $documents;
        $this->referencesMap = $this->buildReferencesMap();
    }

    public function buildCouplingMatrix(): array
    {
        $matrix = [];
        $paperIds = array_keys($this->referencesMap);
        $n = count($paperIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i; $j < $n; $j++) {
                $paperA = $paperIds[$i];
                $paperB = $paperIds[$j];

                $couplingCount = count(array_intersect(
                    $this->referencesMap[$paperA] ?? [],
                    $this->referencesMap[$paperB] ?? []
                ));

                if ($couplingCount > 0) {
                    $matrix[$paperA][$paperB] = $couplingCount;
                    if ($i !== $j) {
                        $matrix[$paperB][$paperA] = $couplingCount;
                    }
                }
            }
        }

        return $matrix;
    }

    public function findCoupledPapers(string $paperId, int $topN = 10): array
    {
        $couplingMatrix = $this->buildCouplingMatrix();

        if (!isset($couplingMatrix[$paperId])) {
            return [];
        }

        $paperCoupling = $couplingMatrix[$paperId];
        arsort($paperCoupling);

        return array_slice($paperCoupling, 0, $topN, preserve_keys: true);
    }

    public function buildCouplingGraph(float $minThreshold = 1): Graph
    {
        $couplingMatrix = $this->buildCouplingMatrix();
        $graph = new Graph(directed: false);

        foreach ($this->documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        foreach ($couplingMatrix as $paperA => $neighbors) {
            foreach ($neighbors as $paperB => $count) {
                if ($count >= $minThreshold && strcmp($paperA, $paperB) < 0) {
                    $graph->addEdge($paperA, $paperB, ['weight' => (float) $count]);
                }
            }
        }

        return $graph;
    }

    public function findCouplingClusters(int $minCoupling = 2): array
    {
        $graph = $this->buildCouplingGraph($minCoupling);
        $visited = [];
        $clusters = [];

        foreach ($graph->nodes() as $nodeId) {
            if (isset($visited[$nodeId])) {
                continue;
            }

            $cluster = [];
            $queue = [$nodeId];
            $visited[$nodeId] = true;

            while (!empty($queue)) {
                $current = array_shift($queue);
                $cluster[] = $current;

                foreach ($graph->edges() as $edge) {
                    $neighbor = null;

                    if ($edge->from === $current) {
                        $neighbor = $edge->to;
                    } elseif ($edge->to === $current) {
                        $neighbor = $edge->from;
                    }

                    if ($neighbor && !isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $queue[] = $neighbor;
                    }
                }
            }

            if (!empty($cluster)) {
                $clusters[] = $cluster;
            }
        }

        return $clusters;
    }

    private function buildReferencesMap(): array
    {
        $map = [];

        foreach ($this->documents as $document) {
            $nodeId = $this->getNodeId($document);
            $references = [];

            if ($document->rawData && isset($document->rawData['referenced_works'])) {
                $references = $document->rawData['referenced_works'];
            }

            if ($document->rawData && isset($document->rawData['references'])) {
                $references = array_merge($references, $document->rawData['references']);
            }

            if ($document->rawData && isset($document->rawData['references_list'])) {
                $references = array_merge($references, $document->rawData['references_list']);
            }

            $map[$nodeId] = array_unique($references);
        }

        return $map;
    }

    private function getNodeId(Document $document): string
    {
        if ($document->externalIds?->doi) {
            return 'doi:' . $document->externalIds->doi;
        }

        return 'id:' . $document->providerId;
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

        return substr($title, 0, $maxLength - 3) . '...';
    }
}
