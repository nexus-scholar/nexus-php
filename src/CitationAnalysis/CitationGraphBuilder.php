<?php

namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class CitationGraphBuilder
{
    public function buildFromDocuments(array $documents): Graph
    {
        return $this->buildCitationGraph($documents);
    }

    public function buildCitationGraph(array $documents, array $citedByMap = []): Graph
    {
        $graph = new Graph(directed: true);
        $docMap = $this->indexDocuments($documents);

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        foreach ($documents as $document) {
            $sourceId = $this->getNodeId($document);

            if ($document->externalIds?->doi) {
                $references = $this->getReferences($document, $citedByMap);
                foreach ($references as $refId) {
                    if (isset($docMap[$refId])) {
                        $graph->addEdge($sourceId, $refId, ['weight' => 1.0]);
                    }
                }
            }
        }

        return $graph;
    }

    public function buildCoCitationGraph(array $documents): Graph
    {
        $graph = new Graph(directed: false);

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        $citingPapers = $this->buildCitingPapersMap($documents);

        $paperIds = array_keys($citingPapers);
        $n = count($paperIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $paperA = $paperIds[$i];
                $paperB = $paperIds[$j];

                $cocitationCount = count(array_intersect(
                    $citingPapers[$paperA] ?? [],
                    $citingPapers[$paperB] ?? []
                ));

                if ($cocitationCount > 0) {
                    $graph->addEdge($paperA, $paperB, ['weight' => (float) $cocitationCount]);
                }
            }
        }

        return $graph;
    }

    public function buildBibliographicCouplingGraph(array $documents): Graph
    {
        $graph = new Graph(directed: false);

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $attributes = $this->documentToNodeAttributes($document);
            $graph->addNode($nodeId, $attributes);
        }

        $referencesMap = $this->buildReferencesMap($documents);

        $paperIds = array_keys($referencesMap);
        $n = count($paperIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $paperA = $paperIds[$i];
                $paperB = $paperIds[$j];

                $couplingCount = count(array_intersect(
                    $referencesMap[$paperA] ?? [],
                    $referencesMap[$paperB] ?? []
                ));

                if ($couplingCount > 0) {
                    $graph->addEdge($paperA, $paperB, ['weight' => (float) $couplingCount]);
                }
            }
        }

        return $graph;
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
            'authors' => implode(', ', array_map(
                fn ($a) => $a->name ?? 'Unknown',
                $document->authors
            )),
        ];
    }

    private function truncateLabel(string $title, int $maxLength = 100): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }

    private function indexDocuments(array $documents): array
    {
        $index = [];

        foreach ($documents as $document) {
            if ($document->externalIds?->doi) {
                $index['doi:'.$document->externalIds->doi] = $document;
            }
            $index['id:'.$document->providerId] = $document;
        }

        return $index;
    }

    private function getReferences(Document $document, array $citedByMap): array
    {
        if (isset($citedByMap[$this->getNodeId($document)])) {
            return $citedByMap[$this->getNodeId($document)];
        }

        if ($document->rawData && isset($document->rawData['referenced_works'])) {
            return $document->rawData['referenced_works'];
        }

        return [];
    }

    private function buildCitingPapersMap(array $documents): array
    {
        $map = [];

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $citingPapers = [];

            if ($document->rawData && isset($document->rawData['citing_papers'])) {
                $citingPapers = $document->rawData['citing_papers'];
            }

            if ($document->rawData && isset($document->rawData['cited_by'])) {
                $citingPapers = array_merge($citingPapers, $document->rawData['cited_by']);
            }

            $map[$nodeId] = $citingPapers;
        }

        return $map;
    }

    private function buildReferencesMap(array $documents): array
    {
        $map = [];

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $references = [];

            if ($document->rawData && isset($document->rawData['referenced_works'])) {
                $references = $document->rawData['referenced_works'];
            }

            $map[$nodeId] = $references;
        }

        return $map;
    }
}
