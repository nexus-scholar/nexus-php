<?php

namespace Nexus\Visualization;

use Mbsoft\Graph\Domain\Graph;
use Nexus\Models\Document;

class GexfExporter
{
    private string $version;

    private string $creator;

    public function __construct(
        ?string $version = '1.3',
        ?string $creator = 'nexus-php'
    ) {
        $this->version = $version;
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

        $docMap = $this->indexDocuments($documents);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><gexf xmlns="http://gexf.net/1.3" version="'.$this->version.'"></gexf>');
        $xml->addAttribute('xmlns:viz', 'http://gexf.net/viz');
        $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xsi:schemaLocation', 'http://gexf.net/1.3 http://gexf.net/1.3/gexf.xsd');

        $meta = $xml->addChild('meta');
        $meta->addAttribute('lastmodifieddate', date('Y-m-d'));
        $meta->addChild('creator', $this->creator);
        $meta->addChild('description', 'Citation Network Export');

        $graphEl = $xml->addChild('graph');
        $graphEl->addAttribute('mode', 'static');
        $graphEl->addAttribute('defaultedgetype', $graph->isDirected() ? 'directed' : 'undirected');

        if ($includeAttributes) {
            $attributes = $graphEl->addChild('attributes');
            $attributes->addAttribute('class', 'node');

            $this->addNodeAttributes($attributes);
        }

        $nodes = $graphEl->addChild('nodes');

        foreach ($graph->nodes() as $nodeId) {
            $nodeAttrs = $graph->nodeAttrs($nodeId);
            $label = $nodeAttrs['label'] ?? $nodeId;

            $nodeEl = $nodes->addChild('node');
            $nodeEl->addAttribute('id', $nodeId);
            $nodeEl->addAttribute('label', $label);

            if ($includeAttributes) {
                $attvalues = $nodeEl->addChild('attvalues');
                $this->addNodeAttValues($attvalues, $nodeAttrs);
            }

            $this->addVizAttributes($nodeEl, $nodeAttrs);
        }

        $edges = $graphEl->addChild('edges');

        foreach ($graph->edges() as $index => $edge) {
            $edgeEl = $edges->addChild('edge');
            $edgeEl->addAttribute('id', (string) $index);
            $edgeEl->addAttribute('source', $edge->from);
            $edgeEl->addAttribute('target', $edge->to);

            $weight = $edge->attributes['weight'] ?? $edge->attributes['weight'] ?? 1.0;
            $edgeEl->addAttribute('weight', (string) $weight);

            if ($graph->isDirected()) {
                $edgeEl->addAttribute('type', 'directed');
            } else {
                $edgeEl->addAttribute('type', 'undirected');
            }
        }

        return $xml->asXML();
    }

    private function addNodeAttributes(\SimpleXMLElement $attributes): void
    {
        $attributeDefs = [
            ['id' => 'title', 'title' => 'title', 'type' => 'string'],
            ['id' => 'year', 'title' => 'year', 'type' => 'integer'],
            ['id' => 'citations', 'title' => 'citations', 'type' => 'integer'],
            ['id' => 'doi', 'title' => 'doi', 'type' => 'string'],
            ['id' => 'venue', 'title' => 'venue', 'type' => 'string'],
            ['id' => 'authors', 'title' => 'authors', 'type' => 'string'],
            ['id' => 'query_id', 'title' => 'query_id', 'type' => 'string'],
        ];

        foreach ($attributeDefs as $def) {
            $attr = $attributes->addChild('attribute');
            $attr->addAttribute('id', $def['id']);
            $attr->addAttribute('title', $def['title']);
            $attr->addAttribute('type', $def['type']);
        }
    }

    private function addNodeAttValues(\SimpleXMLElement $attvalues, array $nodeAttrs): void
    {
        $attrs = [
            'title' => $nodeAttrs['title'] ?? '',
            'year' => $nodeAttrs['year'] ?? '',
            'citations' => $nodeAttrs['citations'] ?? '',
            'doi' => $nodeAttrs['doi'] ?? '',
            'venue' => $nodeAttrs['venue'] ?? '',
            'authors' => $nodeAttrs['authors'] ?? '',
            'query_id' => $nodeAttrs['query_id'] ?? '',
        ];

        foreach ($attrs as $id => $value) {
            if ($value !== '' && $value !== null) {
                $attval = $attvalues->addChild('attvalue');
                $attval->addAttribute('for', $id);
                $attval->addAttribute('value', (string) $value);
            }
        }
    }

    private function addVizAttributes(\SimpleXMLElement $nodeEl, array $nodeAttrs): void
    {
        $viz = $nodeEl->addChild('viz:size', '10');
        $viz->addAttribute('value', '10');

        $year = $nodeAttrs['year'] ?? null;
        if ($year) {
            $colorValue = $this->yearToColor((int) $year);
            $color = $nodeEl->addChild('viz:color');
            $color->addAttribute('r', (string) $colorValue['r']);
            $color->addAttribute('g', (string) $colorValue['g']);
            $color->addAttribute('b', (string) $colorValue['b']);
        }
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
