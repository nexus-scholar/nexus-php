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

class CrossrefProvider extends BaseProvider
{
    private const BASE_URL = 'https://api.crossref.org/works';

    private const ALLOWED_TYPES = [
        'journal-article',
        'proceedings-article',
        'posted-content',
        'book-chapter',
        'monograph',
    ];

    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);

        $fieldMap = [
            QueryField::TITLE->value => 'title',
            QueryField::ABSTRACT->value => 'abstract',
            QueryField::AUTHOR->value => 'author',
            QueryField::VENUE->value => 'container-title',
            QueryField::YEAR->value => 'issued',
            QueryField::DOI->value => 'DOI',
        ];

        $this->translator = new BooleanQueryTranslator($fieldMap);
    }

    public function search(Query $query): Generator
    {
        $params = $this->translateQuery($query);
        $cursor = '*';
        $totalFetched = 0;
        $seenDois = [];
        $maxResults = $query->maxResults ?? PHP_INT_MAX;

        while ($totalFetched < $maxResults) {
            $params['cursor'] = $cursor;
            $response = $this->makeRequest(self::BASE_URL, $params);

            $message = $response['message'] ?? [];
            $items = $message['items'] ?? [];
            $cursor = $message['next-cursor'] ?? null;

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if ($totalFetched >= $maxResults) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc === null) {
                    continue;
                }

                $doi = $doc->externalIds->doi;
                if ($doi && isset($seenDois[$doi])) {
                    continue;
                }

                if ($doi) {
                    $seenDois[$doi] = true;
                }

                $doc->queryId = $query->id;
                $doc->queryText = $query->text;
                yield $doc;
                $totalFetched++;
            }

            if (! $cursor || $cursor === ($params['cursor'] ?? null)) {
                break;
            }
        }
    }

    protected function translateQuery(Query $query): array
    {
        $translation = $this->translator->translate($query);
        $params = [
            'query' => $translation['q'],
            'rows' => 100,
        ];

        $filters = [];
        if ($query->yearMin) {
            $filters[] = "from-pub-date:{$query->yearMin}-01-01";
        }
        if ($query->yearMax) {
            $filters[] = "until-pub-date:{$query->yearMax}-12-31";
        }

        foreach (self::ALLOWED_TYPES as $type) {
            $filters[] = "type:$type";
        }

        if (! empty($filters)) {
            $params['filter'] = implode(',', $filters);
        }

        if ($this->config->mailto) {
            $params['mailto'] = $this->config->mailto;
        }

        return $params;
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        $extractor = new FieldExtractor($raw);
        $titles = $extractor->getList('title');
        $title = $titles[0] ?? null;

        if (! $title) {
            return null;
        }

        $year = $this->extractYear($raw);
        $doi = $extractor->getString('DOI');
        $authors = $this->parseAuthors($raw);
        $abstract = $extractor->getString('abstract');

        $containerTitles = $extractor->getList('container-title');
        $venue = $containerTitles[0] ?? null;

        $url = $extractor->getString('URL');
        $citations = $extractor->getInt('is-referenced-by-count');

        $externalIds = new ExternalIds(doi: $doi);

        return new Document(
            title: $title,
            year: $year,
            provider: 'crossref',
            providerId: $doi ?: substr(md5($title), 0, 16),
            externalIds: $externalIds,
            abstract: $abstract,
            authors: $authors,
            venue: $venue,
            url: $url,
            citedByCount: $citations,
            rawData: $raw
        );
    }

    private function extractYear(array $raw): ?int
    {
        $issued = $raw['issued'] ?? [];
        $dateParts = $issued['date-parts'] ?? [];
        if (! empty($dateParts) && is_array($dateParts[0])) {
            return (int) $dateParts[0][0];
        }

        return null;
    }

    private function parseAuthors(array $raw): array
    {
        $extractor = new FieldExtractor($raw);
        $authorsData = $extractor->getList('author');
        $authors = [];

        foreach ($authorsData as $authorDict) {
            if (! is_array($authorDict)) {
                continue;
            }

            $family = $authorDict['family'] ?? 'Unknown';
            $given = $authorDict['given'] ?? null;
            $orcid = $authorDict['ORCID'] ?? null;

            $authors[] = new Author(familyName: $family, givenName: $given, orcid: $orcid);
        }

        return $authors;
    }
}
