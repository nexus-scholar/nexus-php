<?php

namespace Nexus\Providers;

use Generator;
use Nexus\Models\Author;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Utils\FieldExtractor;

class SemanticScholarProvider extends BaseProvider
{
    private const BASE_URL = 'https://api.semanticscholar.org/graph/v1/paper/search/bulk';
    private const FIELDS = [
        'paperId',
        'corpusId',
        'title',
        'abstract',
        'year',
        'authors',
        'venue',
        'citationCount',
        'referenceCount',
        'influentialCitationCount',
        'isOpenAccess',
        'fieldsOfStudy',
        'publicationTypes',
        'externalIds',
        'url',
    ];

    public function search(Query $query): Generator
    {
        $params = $this->translateQuery($query);
        $token = null;
        $totalFetched = 0;
        $seenPaperIds = [];
        $maxResults = $query->maxResults ?? PHP_INT_MAX;

        while ($totalFetched < $maxResults) {
            if ($token) {
                $params['token'] = $token;
            }

            $response = $this->makeRequest(self::BASE_URL, $params);
            $data = $response['data'] ?? [];
            $token = $response['token'] ?? null;

            if (empty($data)) {
                break;
            }

            foreach ($data as $item) {
                if ($totalFetched >= $maxResults) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc === null) {
                    continue;
                }

                $paperId = $item['paperId'] ?? null;
                if ($paperId && isset($seenPaperIds[$paperId])) {
                    continue;
                }

                if ($paperId) {
                    $seenPaperIds[$paperId] = true;
                }

                $doc->queryId = $query->id;
                $doc->queryText = $query->text;
                yield $doc;
                $totalFetched++;
            }

            if (!$token) {
                break;
            }
        }
    }

    protected function translateQuery(Query $query): array
    {
        $params = [
            'query' => $this->toBulkQuery($query->text),
            'fields' => implode(',', self::FIELDS),
        ];

        if ($query->yearMin && $query->yearMax) {
            $params['year'] = "{$query->yearMin}-{$query->yearMax}";
        } elseif ($query->yearMin) {
            $params['year'] = "{$query->yearMin}-";
        } elseif ($query->yearMax) {
            $params['year'] = "-{$query->yearMax}";
        }

        return $params;
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        $extractor = new FieldExtractor($raw);
        $title = $extractor->getString('title');

        if (!$title) {
            return null;
        }

        $year = $extractor->getInt('year');
        $paperId = $extractor->getString('paperId');
        $externalIdsData = $extractor->getList('externalIds');
        $externalIds = $this->extractExternalIds($externalIdsData);
        $externalIds->s2Id = $paperId;

        $authors = $this->parseAuthors($raw);
        $abstract = $extractor->getString('abstract');
        $venue = $extractor->getString('venue');
        $url = $extractor->getString('url');
        $citations = $extractor->getInt('citationCount');

        return new Document(
            title: $title,
            year: $year,
            provider: 's2',
            providerId: $paperId,
            externalIds: $externalIds,
            abstract: $abstract,
            authors: $authors,
            venue: $venue,
            url: $url,
            citedByCount: $citations,
            rawData: $raw
        );
    }

    private function toBulkQuery(string $text): string
    {
        $q = preg_replace('/\bAND\b/i', '+', $text);
        $q = preg_replace('/\bOR\b/i', '|', $q);
        $q = preg_replace('/\bNOT\b\s+/i', '-', $q);
        return trim((string)preg_replace('/\s+/', ' ', $q));
    }

    private function extractExternalIds(array $data): ExternalIds
    {
        $extractor = new FieldExtractor($data);
        return new ExternalIds(
            doi: $extractor->get('DOI'),
            arxivId: $extractor->get('ArXiv'),
            pubmedId: $extractor->get('PubMed')
        );
    }

    private function parseAuthors(array $raw): array
    {
        $extractor = new FieldExtractor($raw);
        $authorsData = $extractor->getList('authors');
        $authors = [];

        foreach ($authorsData as $authorDict) {
            if (!is_array($authorDict)) {
                continue;
            }

            $name = $authorDict['name'] ?? null;
            if (!$name) {
                continue;
            }

            $parts = explode(' ', $name);
            if (count($parts) === 1) {
                $family = $parts[0];
                $given = null;
            } else {
                $family = array_pop($parts);
                $given = implode(' ', $parts);
            }

            $authors[] = new Author(familyName: $family, givenName: $given);
        }

        return $authors;
    }
}
