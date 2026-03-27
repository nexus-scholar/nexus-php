<?php

namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class SimilarityBuilder
{
    private array $documents = [];
    private CoCitationAnalyzer $coCitationAnalyzer;
    private BibliographicCoupling $bibliographicCoupling;

    public function __construct(array $documents)
    {
        $this->documents = $documents;
        $this->coCitationAnalyzer = new CoCitationAnalyzer($documents);
        $this->bibliographicCoupling = new BibliographicCoupling($documents);
    }

    public function buildCombinedGraph(
        float $cocitationWeight = 0.5,
        float $couplingWeight = 0.5,
        float $threshold = 0.1
    ): Graph {
        $coCitationSimilarity = $this->coCitationAnalyzer->getNormalizedSimilarity();
        $couplingMatrix = $this->bibliographicCoupling->buildCouplingMatrix();

        $maxCoupling = 1;
        foreach ($couplingMatrix as $row) {
            foreach ($row as $value) {
                if ($value > $maxCoupling) {
                    $maxCoupling = $value;
                }
            }
        }

        $graph = new Graph(directed: false);

        foreach ($this->documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        $allPairs = array_unique(array_merge(
            array_keys($coCitationSimilarity),
            array_keys($couplingMatrix)
        ));

        foreach ($allPairs as $paperA) {
            foreach ($allPairs as $paperB) {
                if (strcmp($paperA, $paperB) >= 0) {
                    continue;
                }

                $coCitationScore = $coCitationSimilarity[$paperA][$paperB] ?? 0.0;
                $couplingScore = 0.0;

                if (isset($couplingMatrix[$paperA][$paperB])) {
                    $couplingScore = $couplingMatrix[$paperA][$paperB] / $maxCoupling;
                }

                $combinedScore = ($cocitationWeight * $coCitationScore) + ($couplingWeight * $couplingScore);

                if ($combinedScore >= $threshold) {
                    $graph->addEdge($paperA, $paperB, ['weight' => $combinedScore]);
                }
            }
        }

        return $graph;
    }

    public function buildSimilarityNetwork(): Graph
    {
        return $this->coCitationAnalyzer->buildSimilarityGraph(0.1);
    }

    public function getSimilarityMatrix(): array
    {
        $normalized = $this->coCitationAnalyzer->getNormalizedSimilarity();
        $coupling = $this->bibliographicCoupling->buildCouplingMatrix();

        $combined = $normalized;

        foreach ($coupling as $paperA => $neighbors) {
            foreach ($neighbors as $paperB => $count) {
                if (!isset($combined[$paperA])) {
                    $combined[$paperA] = [];
                }
                if (!isset($combined[$paperA][$paperB])) {
                    $combined[$paperA][$paperB] = 0.0;
                }
                $combined[$paperA][$paperB] += (float) $count;
            }
        }

        return $combined;
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
