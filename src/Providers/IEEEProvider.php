<?php

namespace Nexus\Providers;

use Generator;
use Nexus\Models\Author;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Models\QueryField;
use Nexus\Utils\BooleanQueryTranslator;
use Nexus\Utils\FieldExtractor;
use Nexus\Utils\Exceptions\AuthenticationError;

class IEEEProvider extends BaseProvider
{
    private const BASE_URL = 'https://ieeexploreapi.ieee.org/api/v1/search/articles';

    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);

        if ($config->rateLimit == 1.0) {
            $config->rateLimit = 1.0;
        }

        $fieldMap = [
            QueryField::TITLE->value => 'article_title',
            QueryField::ABSTRACT->value => 'abstract',
            QueryField::AUTHOR->value => 'author',
            QueryField::VENUE->value => 'publication_title',
            QueryField::YEAR->value => 'publication_year',
            QueryField::DOI->value => 'doi',
        ];

        $this->translator = new BooleanQueryTranslator($fieldMap);
    }

    public function search(Query $query): Generator
    {
        if (!$this->config->apiKey) {
            throw new AuthenticationError('ieee', 'API key is required for IEEE Xplore');
        }

        $translation = $this->translator->translate($query);

        $params = [
            'apikey' => $this->config->apiKey,
            'querytext' => $translation['q'],
            'format' => 'json',
            'max_records' => 100,
            'start_record' => 1,
            'sort_field' => 'publication_year',
            'sort_order' => 'desc',
        ];

        if ($query->yearMin) {
            $params['start_year'] = $query->yearMin;
        }
        if ($query->yearMax) {
            $params['end_year'] = $query->yearMax;
        }

        $totalFetched = 0;
        $maxResults = $query->maxResults ?? PHP_INT_MAX;

        while ($totalFetched < $maxResults) {
            try {
                $response = $this->makeRequest(self::BASE_URL, $params);
            } catch (\Throwable $e) {
                break;
            }

            $articles = $response['articles'] ?? [];
            if (empty($articles)) {
                break;
            }

            foreach ($articles as $item) {
                if ($totalFetched >= $maxResults) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc) {
                    $doc->queryId = $query->id;
                    $doc->queryText = $query->text;
                    yield $doc;
                    $totalFetched++;
                }
            }

            $totalRecords = $response['total_records'] ?? 0;
            if ($totalFetched >= $totalRecords || count($articles) < $params['max_records']) {
                break;
            }

            $params['start_record'] += count($articles);
        }
    }

    protected function translateQuery(Query $query): array
    {
        return [];
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        try {
            $extractor = new FieldExtractor($raw);

            $title = $extractor->getString('title');
            if (!$title) {
                return null;
            }

            $year = $extractor->getInt('publication_year');

            $authors = [];
            $authorData = $raw['authors'] ?? [];
            if (is_array($authorData) && isset($authorData['authors'])) {
                foreach ($authorData['authors'] as $au) {
                    $fullName = $au['full_name'] ?? null;
                    if (!$fullName) {
                        continue;
                    }

                    $parsed = $this->parseAuthorName($fullName);
                    $authors[] = new Author(
                        familyName: $parsed['family'],
                        givenName: $parsed['given']
                    );
                }
            }

            $abstract = $extractor->getString('abstract');
            $venue = $extractor->getString('publication_title');
            $doi = $extractor->getString('doi');
            $articleNumber = $extractor->getString('article_number');
            $url = $extractor->getString('html_url');

            $externalIds = new ExternalIds(doi: $doi !== '' ? $doi : null);

            return new Document(
                title: $title,
                year: $year,
                abstract: $abstract !== '' ? $abstract : null,
                authors: $authors,
                venue: $venue !== '' ? $venue : null,
                url: $url !== '' ? $url : null,
                externalIds: $externalIds,
                provider: 'ieee',
                providerId: $articleNumber !== '' ? $articleNumber : ($doi ?: (string) abs(crc32($title))),
                rawData: null
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseAuthorName(string $name): array
    {
        $name = trim($name);

        if (str_contains($name, ',')) {
            $parts = explode(',', $name, 2);

            return [
                'family' => trim($parts[0]),
                'given' => isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null,
            ];
        }

        $parts = preg_split('/\s+/', $name);
        if (count($parts) === 1) {
            return ['family' => $parts[0], 'given' => null];
        }

        return [
            'family' => array_pop($parts),
            'given' => implode(' ', $parts),
        ];
    }
}
