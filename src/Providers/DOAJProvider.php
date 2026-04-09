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

class DOAJProvider extends BaseProvider
{
    private const BASE_URL = 'https://doaj.org/api/v1/search/articles';

    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);

        $fieldMap = [
            QueryField::TITLE->value => 'bibjson.title',
            QueryField::ABSTRACT->value => 'bibjson.abstract',
            QueryField::AUTHOR->value => 'bibjson.author.name',
            QueryField::VENUE->value => 'bibjson.journal.title',
            QueryField::YEAR->value => 'bibjson.year',
            QueryField::DOI->value => 'bibjson.doi',
        ];

        $this->translator = new BooleanQueryTranslator($fieldMap);
    }

    public function search(Query $query): Generator
    {
        $translation = $this->translator->translate($query);
        $searchText = $translation['q'];

        $yearFilter = '';
        if ($query->yearMin && $query->yearMax) {
            $yearFilter = " AND bibjson.year:[{$query->yearMin} TO {$query->yearMax}]";
        } elseif ($query->yearMin) {
            $yearFilter = " AND bibjson.year:[{$query->yearMin} TO 3000]";
        } elseif ($query->yearMax) {
            $yearFilter = " AND bibjson.year:[1000 TO {$query->yearMax}]";
        }

        if ($yearFilter) {
            $searchText = "({$searchText}){$yearFilter}";
        }

        $page = 1;
        $pageSize = 100;
        $totalFetched = 0;
        $maxResults = $query->maxResults ?? 1000;

        while ($totalFetched < $maxResults) {
            $url = self::BASE_URL.'/'.urlencode($searchText);
            $params = [
                'page' => $page,
                'pageSize' => $pageSize,
            ];

            try {
                $response = $this->makeRequest($url, $params);
            } catch (\Throwable $e) {
                break;
            }

            $results = $response['results'] ?? [];
            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
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

            $totalAvailable = $response['total'] ?? 0;
            if ($totalFetched >= $totalAvailable || count($results) < $pageSize) {
                break;
            }

            $page++;
        }
    }

    protected function translateQuery(Query $query): array
    {
        return [];
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        try {
            $bibjson = $raw['bibjson'] ?? [];
            $extractor = new FieldExtractor($bibjson);

            $title = $extractor->getString('title');
            if (! $title) {
                return null;
            }

            $yearVal = $extractor->get('year');
            $year = null;
            if ($yearVal !== null) {
                try {
                    $year = (int) $yearVal;
                } catch (\Throwable) {
                }
            }

            $authors = [];
            $authorList = $extractor->getList('author');
            foreach ($authorList as $au) {
                $name = $au['name'] ?? null;
                if (! $name) {
                    continue;
                }

                $parsed = $this->parseAuthorName((string) $name);
                $authors[] = new Author(
                    familyName: $parsed['family'],
                    givenName: $parsed['given']
                );
            }

            $abstract = $extractor->getString('abstract');
            $venue = $extractor->getString('journal.title');

            $doi = null;
            $url = null;
            $identifiers = $extractor->getList('identifier');
            foreach ($identifiers as $ident) {
                $type = $ident['type'] ?? null;
                $id = $ident['id'] ?? null;
                if (! $type || ! $id) {
                    continue;
                }

                if ($type === 'doi' && $doi === null) {
                    $doi = (string) $id;
                } elseif ($type === 'url' && $url === null) {
                    $url = (string) $id;
                }
            }

            if (! $url && $doi) {
                $url = "https://doi.org/{$doi}";
            }

            $externalIds = new ExternalIds(doi: $doi !== '' ? $doi : null);

            $providerId = $raw['id'] ?? ($doi ?: (string) abs(crc32($title)));

            return new Document(
                title: $title,
                year: $year,
                abstract: $abstract !== '' ? $abstract : null,
                authors: $authors,
                venue: $venue !== '' ? $venue : null,
                url: $url,
                externalIds: $externalIds,
                provider: 'doaj',
                providerId: $providerId,
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
