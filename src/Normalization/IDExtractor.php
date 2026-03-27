<?php

declare(strict_types=1);

namespace Nexus\Normalization;

use Nexus\Models\ExternalIds;

class IDExtractor
{
    public function __construct(
        protected array $data
    ) {}

    public function get(string $path, mixed $default = null): mixed
    {
        $parts = explode('.', $path);
        $current = $this->data;

        foreach ($parts as $part) {
            if ($current === null) {
                return $default;
            }

            if (is_array($current)) {
                $current = $current[$part] ?? $default;
            } else {
                return $default;
            }
        }

        return $current ?? $default;
    }

    public function getFirst(mixed ...$paths): mixed
    {
        foreach ($paths as $path) {
            $value = $this->get($path);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    public function extractDoi(?string ...$paths): ?string
    {
        $doi = $paths !== [] ? $this->getFirst(...$paths) : $this->get('doi');
        if (!$doi) {
            return null;
        }
        return trim((string) $doi);
    }

    public function extractArxivId(?string ...$paths): ?string
    {
        $arxivId = $paths !== [] ? $this->getFirst(...$paths) : $this->get('arxiv_id');
        if (!$arxivId) {
            return null;
        }

        $arxivStr = trim((string) $arxivId);
        $arxivStr = preg_replace('/^arxiv:arXiv:/i', '', $arxivStr);
        $arxivStr = preg_replace('/^arXiv:/i', '', $arxivStr);

        return $arxivStr ?: null;
    }

    public function extractPmid(?string ...$paths): ?string
    {
        $pmid = $paths !== [] ? $this->getFirst(...$paths) : $this->get('pmid');
        return $pmid !== null ? trim((string) $pmid) : null;
    }

    public function extractOpenalexId(?string ...$paths): ?string
    {
        $oaId = $paths !== [] ? $this->getFirst(...$paths) : $this->get('id');
        if (!$oaId) {
            return null;
        }

        $oaStr = (string) $oaId;

        if (str_contains($oaStr, 'openalex.org')) {
            if (preg_match('/(W\d+)/', $oaStr, $matches)) {
                return $matches[1];
            }
        }

        return trim($oaStr);
    }

    public function extractS2Id(?string ...$paths): ?string
    {
        $s2Id = $paths !== [] ? $this->getFirst(...$paths) : $this->get('corpusId');
        return $s2Id !== null ? trim((string) $s2Id) : null;
    }

    public function extractAll(
        ?array $doiPaths = null,
        ?array $arxivPaths = null,
        ?array $pmidPaths = null,
        ?array $openalexPaths = null,
        ?array $s2Paths = null
    ): ExternalIds {
        return new ExternalIds(
            doi: $doiPaths !== null ? $this->extractDoi(...$doiPaths) : $this->extractDoi(),
            arxivId: $arxivPaths !== null ? $this->extractArxivId(...$arxivPaths) : $this->extractArxivId(),
            pubmedId: $pmidPaths !== null ? $this->extractPmid(...$pmidPaths) : $this->extractPmid(),
            openalexId: $openalexPaths !== null ? $this->extractOpenalexId(...$openalexPaths) : $this->extractOpenalexId(),
            s2Id: $s2Paths !== null ? $this->extractS2Id(...$s2Paths) : $this->extractS2Id()
        );
    }
}
