<?php

namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class CytoscapeExporter
{
    public function export(
        Graph $graph,
        ?array $documents = [],
        ?array $options = []
    ): array {
        $includeAttributes = $options['include_attributes'] ?? true;
        $edgeWeightAttribute = $options['edge_weight_attribute'] ?? 'weight';

        $docMap = $this->indexDocuments($documents);

        $nodes = [];
        foreach ($graph->nodes() as $nodeId) {
            $nodeAttrs = $graph->nodeAttrs($nodeId);
            $label = $nodeAttrs['label'] ?? $nodeId;

            $nodeData = [
                'data' => [
                    'id' => $nodeId,
                    'label' => $label,
                ],
            ];

            if ($includeAttributes) {
                $nodeData['data']['title'] = $nodeAttrs['title'] ?? '';
                $nodeData['data']['year'] = $nodeAttrs['year'] ?? null;
                $nodeData['data']['citations'] = $nodeAttrs['citations'] ?? 0;
                $nodeData['data']['doi'] = $nodeAttrs['doi'] ?? '';
                $nodeData['data']['venue'] = $nodeAttrs['venue'] ?? '';
                $nodeData['data']['authors'] = $nodeAttrs['authors'] ?? '';
                $nodeData['data']['query_id'] = $nodeAttrs['query_id'] ?? '';
            }

            $year = $nodeAttrs['year'] ?? null;
            if ($year) {
                $colorValue = $this->yearToColor((int) $year);
                $nodeData['data']['color'] = sprintf('#%02x%02x%02x', $colorValue['r'], $colorValue['g'], $colorValue['b']);
            }

            $nodes[] = $nodeData;
        }

        $edges = [];
        foreach ($graph->edges() as $index => $edge) {
            $edgeData = [
                'data' => [
                    'id' => 'e'.$index,
                    'source' => $edge->from,
                    'target' => $edge->to,
                    'weight' => $edge->attributes['weight'] ?? 1.0,
                ],
            ];

            if ($graph->isDirected()) {
                $edgeData['data']['interaction'] = 'cites';
            } else {
                $edgeData['data']['interaction'] = 'similar_to';
            }

            $edges[] = $edgeData;
        }

        return [
            'data' => [
                'creator' => 'nexus-php',
                'name' => 'Citation Network',
                'generated' => date('c'),
            ],
            'data_schema' => [
                'nodes' => ['title', 'year', 'citations', 'doi', 'venue', 'authors', 'query_id'],
                'edges' => ['weight', 'interaction'],
            ],
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }

    private function yearToColor(int $year): array
    {
        $minYear = 1990;
        $maxYear = (int) date('Y');
        $normalized = ($year - $minYear) / max(1, $maxYear - $minYear);

        $normalized = max(0, min(1, $normalized));

        $r = (int) (50 + ($normalized * 205));
        $g = (int) (50 + ($normalized * 100));
        $b = (int) (205 - ($normalized * 155));

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    private function indexDocuments(array $documents): array
    {
        $index = [];

        foreach ($documents as $document) {
            $nodeId = $this->getNodeId($document);
            $index[$nodeId] = $document;
        }

        return $index;
    }

    private function getNodeId(Document $document): string
    {
        if ($document->externalIds?->doi) {
            return 'doi:'.$document->externalIds->doi;
        }

        return 'id:'.$document->providerId;
    }
}
