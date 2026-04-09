<?php

namespace Nexus\Models;

class ExternalIds
{
    public function __construct(
        public ?string $doi = null,
        public ?string $arxivId = null,
        public ?string $pubmedId = null,
        public ?string $openalexId = null,
        public ?string $s2Id = null
    ) {
        $this->doi = $this->normalizeDoi($doi);
    }

    private function normalizeDoi(?string $doi): ?string
    {
        if (! $doi) {
            return null;
        }

        // Remove https://doi.org/ or http://dx.doi.org/ prefixes
        $doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $doi);
        // Remove doi: prefix
        $doi = preg_replace('/^doi:\s*/i', '', $doi);

        return strtolower(trim($doi));
    }

    public function toArray(): array
    {
        return [
            'doi' => $this->doi,
            'arxiv_id' => $this->arxivId,
            'pubmed_id' => $this->pubmedId,
            'openalex_id' => $this->openalexId,
            's2_id' => $this->s2Id,
        ];
    }
}
