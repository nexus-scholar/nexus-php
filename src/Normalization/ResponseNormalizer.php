<?php

declare(strict_types=1);

namespace Nexus\Normalization;

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Author;

class ResponseNormalizer
{
    public function __construct(
        protected string $providerName
    ) {}

    public function normalize(
        array $data,
        array $fieldMap,
        ?callable $authorParser = null,
        ?callable $idExtractor = null
    ): ?Document {
        try {
            $titleField = $fieldMap['title'] ?? 'title';
            $title = $this->getString($data, $titleField);

            if (!$title) {
                return null;
            }

            $yearField = $fieldMap['year'] ?? 'year';
            $yearValue = $this->get($data, $yearField);
            $year = DateParser::extractYear($yearValue);

            if ($authorParser !== null) {
                $authors = $authorParser($data);
            } else {
                $authorsData = $this->getList($data, $fieldMap['authors'] ?? 'authors');
                $authors = AuthorParser::parseAuthors($authorsData);
            }

            if ($idExtractor !== null) {
                $externalIds = $idExtractor($data);
            } else {
                $idExt = new IDExtractor($data);
                $externalIds = $idExt->extractAll();
            }

            $abstract = $this->getString($data, $fieldMap['abstract'] ?? 'abstract');
            $venue = $this->getString($data, $fieldMap['venue'] ?? 'venue');
            $url = $this->getString($data, $fieldMap['url'] ?? 'url');
            $citations = $this->getInt($data, $fieldMap['citations'] ?? 'cited_by_count');

            $providerId = $externalIds->doi
                ?? $externalIds->openalexId
                ?? $externalIds->arxivId
                ?? $externalIds->s2Id
                ?? substr((string) md5($title), 0, 16);

            return new Document(
                title: $title,
                year: $year,
                provider: $this->providerName,
                providerId: $providerId,
                externalIds: $externalIds,
                abstract: $abstract,
                authors: $authors,
                venue: $venue,
                url: $url,
                citedByCount: $citations,
                rawData: $data
            );
        } catch (\Throwable $e) {
            error_log("Failed to normalize document: {$e->getMessage()}");
            return null;
        }
    }

    protected function get(array $data, string $path, mixed $default = null): mixed
    {
        $parts = explode('.', $path);
        $current = $data;

        foreach ($parts as $part) {
            if ($current === null || !is_array($current)) {
                return $default;
            }
            $current = $current[$part] ?? $default;
        }

        return $current ?? $default;
    }

    protected function getString(array $data, string $path, string $default = ''): string
    {
        $value = $this->get($data, $path);
        return $value !== null ? trim((string) $value) : $default;
    }

    protected function getInt(array $data, string $path, ?int $default = null): ?int
    {
        $value = $this->get($data, $path);
        if ($value === null) {
            return $default;
        }

        try {
            return (int) $value;
        } catch (\Throwable) {
            return $default;
        }
    }

    protected function getList(array $data, string $path, ?array $default = null): array
    {
        $value = $this->get($data, $path);
        return is_array($value) ? $value : ($default ?? []);
    }
}
