<?php

namespace Nexus\Models;

class DocumentCluster
{
    public function __construct(
        public int $clusterId,
        public Document $representative,
        public array $members,
        public array $allDois = [],
        public array $allArxivIds = [],
        public array $providerCounts = []
    ) {}

    public function size(): int
    {
        return count($this->members);
    }

    public function toArray(): array
    {
        return [
            'cluster_id' => $this->clusterId,
            'representative' => $this->representative->toArray(),
            'members' => array_map(fn (Document $d) => $d->toArray(), $this->members),
            'all_dois' => $this->allDois,
            'all_arxiv_ids' => $this->allArxivIds,
            'provider_counts' => $this->providerCounts,
            'size' => $this->size(),
        ];
    }
}
