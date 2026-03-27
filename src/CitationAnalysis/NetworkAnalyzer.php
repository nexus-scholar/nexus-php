<?php

namespace Nexus\CitationAnalysis;

use Mbsoft\Graph\Domain\Graph;
use Mbsoft\Graph\Domain\Edge;
use Mbsoft\Graph\Algorithms\Centrality\PageRank;
use Mbsoft\Graph\Algorithms\Components\StronglyConnected;

class NetworkAnalyzer
{
    private array $adjacencyList = [];
    private array $reverseAdjacencyList = [];
    private array $nodes = [];

    public function __construct(private Graph $graph)
    {
        $this->buildAdjacencyLists();
    }

    public function findInfluentialPapers(int $topN = 10): array
    {
        $pagerank = new PageRank();
        $scores = $pagerank->compute($this->graph);

        arsort($scores);

        return array_slice($scores, 0, $topN, preserve_keys: true);
    }

    public function getDegreeCentrality(): array
    {
        $centrality = [];

        foreach ($this->nodes as $nodeId) {
            $inDegree = count($this->reverseAdjacencyList[$nodeId] ?? []);
            $outDegree = count($this->adjacencyList[$nodeId] ?? []);
            $centrality[$nodeId] = [
                'in_degree' => $inDegree,
                'out_degree' => $outDegree,
                'total' => $inDegree + $outDegree,
            ];
        }

        return $centrality;
    }

    public function findClusters(): array
    {
        if ($this->graph->isDirected()) {
            $scc = new StronglyConnected();
            return $scc->findComponents($this->graph);
        }

        return $this->findUndirectedComponents();
    }

    public function findKCore(int $k): Graph
    {
        $kCoreGraph = new Graph(directed: $this->graph->isDirected());
        $nodeDegrees = $this->calculateNodeDegrees();

        $remainingNodes = array_keys($nodeDegrees);
        $removed = [];

        while (true) {
            $toRemove = [];

            foreach ($remainingNodes as $nodeId) {
                if (($nodeDegrees[$nodeId] ?? 0) < $k) {
                    $toRemove[] = $nodeId;
                }
            }

            if (empty($toRemove)) {
                break;
            }

            foreach ($toRemove as $nodeId) {
                $removed[$nodeId] = true;
                $remainingNodes = array_filter($remainingNodes, fn($id) => $id !== $nodeId);

                foreach ($this->adjacencyList[$nodeId] ?? [] as $neighbor) {
                    if (!isset($removed[$neighbor])) {
                        $nodeDegrees[$neighbor] = ($nodeDegrees[$neighbor] ?? 1) - 1;
                    }
                }

                foreach ($this->reverseAdjacencyList[$nodeId] ?? [] as $neighbor) {
                    if (!isset($removed[$neighbor])) {
                        $nodeDegrees[$neighbor] = ($nodeDegrees[$neighbor] ?? 1) - 1;
                    }
                }
            }
        }

        foreach ($remainingNodes as $nodeId) {
            $attrs = $this->graph->nodeAttrs($nodeId);
            $kCoreGraph->addNode($nodeId, $attrs);
        }

        foreach ($remainingNodes as $nodeId) {
            foreach ($this->adjacencyList[$nodeId] ?? [] as $target) {
                if (in_array($target, $remainingNodes)) {
                    $edgeAttrs = $this->graph->edgeAttrs($nodeId, $target);
                    $kCoreGraph->addEdge($nodeId, $target, $edgeAttrs);
                }
            }
        }

        return $kCoreGraph;
    }

    public function traverseCitations(string $seedId, int $depth): array
    {
        $visited = [];
        $queue = [[$seedId, 0]];
        $visited[$seedId] = true;

        while (!empty($queue)) {
            [$current, $currentDepth] = array_shift($queue);

            if ($currentDepth >= $depth) {
                continue;
            }

            foreach ($this->adjacencyList[$current] ?? [] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = [$neighbor, $currentDepth + 1];
                }
            }

            foreach ($this->reverseAdjacencyList[$current] ?? [] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = [$neighbor, $currentDepth + 1];
                }
            }
        }

        unset($visited[$seedId]);

        return array_keys($visited);
    }

    public function findCitationPath(string $fromId, string $toId): ?array
    {
        if ($fromId === $toId) {
            return [$fromId];
        }

        $visited = [];
        $queue = [[$fromId, [$fromId]]];
        $visited[$fromId] = true;

        while (!empty($queue)) {
            [$current, $path] = array_shift($queue);

            foreach ($this->adjacencyList[$current] ?? [] as $neighbor) {
                if ($neighbor === $toId) {
                    return array_merge($path, [$neighbor]);
                }

                if (!isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = [$neighbor, array_merge($path, [$neighbor])];
                }
            }
        }

        return null;
    }

    private function buildAdjacencyLists(): void
    {
        foreach ($this->graph->nodes() as $nodeId) {
            $this->nodes[] = $nodeId;
            $this->adjacencyList[$nodeId] = [];
            $this->reverseAdjacencyList[$nodeId] = [];
        }

        foreach ($this->graph->edges() as $edge) {
            $source = $edge->from;
            $target = $edge->to;

            if (!isset($this->adjacencyList[$source])) {
                $this->adjacencyList[$source] = [];
            }
            if (!isset($this->reverseAdjacencyList[$target])) {
                $this->reverseAdjacencyList[$target] = [];
            }

            $this->adjacencyList[$source][] = $target;
            $this->reverseAdjacencyList[$target][] = $source;
        }
    }

    private function calculateNodeDegrees(): array
    {
        $degrees = [];

        foreach ($this->adjacencyList as $nodeId => $neighbors) {
            $degrees[$nodeId] = ($degrees[$nodeId] ?? 0) + count($neighbors);
        }

        foreach ($this->reverseAdjacencyList as $nodeId => $neighbors) {
            $degrees[$nodeId] = ($degrees[$nodeId] ?? 0) + count($neighbors);
        }

        return $degrees;
    }

    private function findUndirectedComponents(): array
    {
        $visited = [];
        $components = [];

        foreach ($this->nodes as $nodeId) {
            if (isset($visited[$nodeId])) {
                continue;
            }

            $component = [];
            $queue = [$nodeId];
            $visited[$nodeId] = true;

            while (!empty($queue)) {
                $current = array_shift($queue);
                $component[] = $current;

                foreach ($this->adjacencyList[$current] ?? [] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $queue[] = $neighbor;
                    }
                }

                foreach ($this->reverseAdjacencyList[$current] ?? [] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $queue[] = $neighbor;
                    }
                }
            }

            $components[] = $component;
        }

        return $components;
    }
}
