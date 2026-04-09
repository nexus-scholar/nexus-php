<?php

namespace Nexus\Models;

class Query
{
    public function __construct(
        public string $text,
        public ?string $id = null,
        public ?int $yearMin = null,
        public ?int $yearMax = null,
        public string $language = 'en',
        public ?int $maxResults = null,
        public int $offset = 0,
        public array $metadata = []
    ) {
        $this->id = $id ?? 'Q'.substr(uniqid(), -5);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'year_min' => $this->yearMin,
            'year_max' => $this->yearMax,
            'language' => $this->language,
            'max_results' => $this->maxResults,
            'offset' => $this->offset,
            'metadata' => $this->metadata,
        ];
    }
}
