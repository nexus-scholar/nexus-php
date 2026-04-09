<?php

namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class GraphMLExporter
{
    private string $creator;

    public function __construct(?string $creator = 'nexus-php')
    {
        $this->creator = $creator;
    }

    public function export(
        Graph $graph,
        ?array $documents = [],
        ?array $options = []
    ): string {
        $includeAttributes = $options['include_attributes'] ?? true;
        $edgeWeightAttribute = $options['edge_weight_attribute'] ?? 'weight';
        $nodeColorAttribute = $options['node_color_attribute'] ?? null;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><graphml xmlns="http://graphml.graphdrawing.org/xmlns" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd"></graphml>');

        $keyId = '0';
        $dataKeys = [];

        if ($includeAttributes) {
            $dataKeys = $this->addAttributeKeys($xml, $keyId);
        }

        $edgeType = $graph->isDirected() ? 'directed' : 'undirected';
        $graphEl = $xml->addChild('graph');
        $graphEl->addAttribute('id', 'G');
        $graphEl->addAttribute('edgedefault', $edgeType);

        $docMap = $this->indexDocuments($documents);

        foreach ($graph->nodes() as $nodeId) {
            $nodeAttrs = $graph->nodeAttrs($nodeId);
            $label = $nodeAttrs['label'] ?? $nodeId;

            $nodeEl = $graphEl->addChild('node');
            $nodeEl->addAttribute('id', $this->escapeXml($nodeId));

            if ($includeAttributes) {
                $dataEl = $nodeEl->addChild('data');
                $dataEl->addAttribute('key', $dataKeys['label']);
                $dataEl[0] = $this->escapeXml($label);

                foreach (['title', 'year', 'citations', 'doi', 'venue', 'authors', 'query_id'] as $attr) {
                    if (isset($dataKeys[$attr])) {
                        $dataEl = $nodeEl->addChild('data');
                        $dataEl->addAttribute('key', $dataKeys[$attr]);
                        $dataEl[0] = $this->escapeXml((string) ($nodeAttrs[$attr] ?? ''));
                    }
                }
            }
        }

        foreach ($graph->edges() as $index => $edge) {
            $edgeEl = $graphEl->addChild('edge');
            $edgeEl->addAttribute('id', 'e'.$index);
            $edgeEl->addAttribute('source', $this->escapeXml($edge->from));
            $edgeEl->addAttribute('target', $this->escapeXml($edge->to));

            if ($includeAttributes && isset($edge->attributes['weight'])) {
                $dataEl = $edgeEl->addChild('data');
                $dataEl->addAttribute('key', $dataKeys['weight'] ?? 'weight');
                $dataEl[0] = (string) $edge->attributes['weight'];
            }
        }

        return $xml->asXML();
    }

    private function addAttributeKeys(\SimpleXMLElement $xml, string &$startId): array
    {
        $keys = [];
        $attributeDefs = [
            'label' => ['title' => 'label', 'type' => 'string'],
            'title' => ['title' => 'title', 'type' => 'string'],
            'year' => ['title' => 'year', 'type' => 'int'],
            'citations' => ['title' => 'citations', 'type' => 'int'],
            'doi' => ['title' => 'doi', 'type' => 'string'],
            'venue' => ['title' => 'venue', 'type' => 'string'],
            'authors' => ['title' => 'authors', 'type' => 'string'],
            'query_id' => ['title' => 'query_id', 'type' => 'string'],
            'weight' => ['title' => 'weight', 'type' => 'double'],
        ];

        $currentId = (int) $startId;

        foreach ($attributeDefs as $key => $def) {
            $keyEl = $xml->addChild('key');
            $keyEl->addAttribute('id', (string) $currentId);
            $keyEl->addAttribute('for', $key === 'weight' ? 'edge' : 'node');
            $keyEl->addAttribute('attr.name', $def['title']);
            $keyEl->addAttribute('attr.type', $def['type']);

            $keys[$key] = (string) $currentId;
            $currentId++;
        }

        return $keys;
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

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
