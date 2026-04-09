<?php

namespace Nexus\Models;

use DateTime;

class Document
{
    /**
     * @param  Author[]  $authors
     */
    public function __construct(
        public string $title,
        public ?int $year = null,
        public string $provider = 'unknown',
        public string $providerId = '',
        public ?ExternalIds $externalIds = null,
        public ?string $abstract = null,
        public array $authors = [],
        public ?string $venue = null,
        public ?string $url = null,
        public ?string $language = null,
        public ?int $citedByCount = null,
        public ?string $queryId = null,
        public ?string $queryText = null,
        public ?DateTime $retrievedAt = null,
        public ?int $clusterId = null,
        public ?array $rawData = null
    ) {
        $this->externalIds = $externalIds ?? new ExternalIds;
        $this->retrievedAt = $retrievedAt ?? new DateTime;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'external_ids' => $this->externalIds->toArray(),
            'abstract' => $this->abstract,
            'authors' => array_map(fn (Author $author) => $author->toArray(), $this->authors),
            'venue' => $this->venue,
            'url' => $this->url,
            'language' => $this->language,
            'cited_by_count' => $this->citedByCount,
            'query_id' => $this->queryId,
            'query_text' => $this->queryText,
            'retrieved_at' => $this->retrievedAt?->format(DateTime::ATOM),
            'cluster_id' => $this->clusterId,
        ];
    }
}
