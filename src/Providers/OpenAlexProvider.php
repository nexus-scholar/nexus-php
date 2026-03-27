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
use Nexus\Core\SnowballProviderInterface;

class OpenAlexProvider extends BaseProvider implements SnowballProviderInterface
{
    private const BASE_URL = 'https://api.openalex.org/works';
    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);
        
        $fieldMap = [
            QueryField::TITLE->value => 'title',
            QueryField::ABSTRACT->value => 'abstract',
            QueryField::AUTHOR->value => 'authorships.author.display_name',
            QueryField::VENUE->value => 'primary_location.source.display_name',
            QueryField::YEAR->value => 'publication_year',
            QueryField::DOI->value => 'doi',
        ];
        
        $this->translator = new BooleanQueryTranslator($fieldMap);
    }

    public function search(Query $query): Generator
    {
        $params = $this->translateQuery($query);
        $totalRetrieved = 0;
        $maxResults = $query->maxResults ?? PHP_INT_MAX;
        $seenIds = [];

        $params['per-page'] = 200;
        $params['cursor'] = '*';
        $params['select'] = 'id,doi,display_name,title,publication_year,publication_date,primary_location,authorships,cited_by_count,biblio,is_retracted,type,open_access,abstract_inverted_index';

        while ($totalRetrieved < $maxResults) {
            $response = $this->makeRequest(self::BASE_URL, $params);
            
            $results = $response['results'] ?? [];
            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
                if ($totalRetrieved >= $maxResults) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc && !isset($seenIds[$doc->providerId])) {
                    $doc->queryId = $query->id;
                    $doc->queryText = $query->text;
                    yield $doc;
                    $seenIds[$doc->providerId] = true;
                    $totalRetrieved++;
                }
            }

            $nextCursor = $response['meta']['next_cursor'] ?? null;
            if (!$nextCursor || $nextCursor === ($params['cursor'] ?? null)) {
                break;
            }

            $params['cursor'] = $nextCursor;
        }
    }

    protected function translateQuery(Query $query): array
    {
        $translation = $this->translator->translate($query);
        $params = [
            'search' => $translation['q'],
        ];

        $filters = [];
        if ($query->yearMin || $query->yearMax) {
            $yearMin = $query->yearMin ?? 1900;
            $yearMax = $query->yearMax ?? (int)date('Y');
            $filters[] = "publication_year:{$yearMin}-{$yearMax}";
        }

        if ($query->language) {
            $filters[] = "language:{$query->language}";
        }

        $filters[] = "type:article|review";

        if (!empty($filters)) {
            $params['filter'] = implode(',', $filters);
        }

        if ($this->config->mailto) {
            $params['mailto'] = $this->config->mailto;
        }

        return $params;
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        try {
            $extractor = new FieldExtractor($raw);
            
            $title = $extractor->getString('display_name') ?: $extractor->getString('title');
            if (!$title) {
                return null;
            }

            $year = $extractor->getInt('publication_year');
            $authors = $this->parseAuthors($raw);
            $externalIds = $this->extractIds($raw);
            $abstract = $this->extractAbstract($raw);
            $venue = $extractor->getString('primary_location.source.display_name');
            
            $openalexId = $extractor->getString('id');
            if (str_contains($openalexId, 'openalex.org/')) {
                $parts = explode('/', $openalexId);
                $openalexId = end($parts);
            }

            $providerId = $openalexId ?: ($externalIds->doi ?: substr(md5($title), 0, 16));
            $citations = $extractor->getInt('cited_by_count', 0);
            $url = $openalexId ? "https://openalex.org/{$openalexId}" : null;

            return new Document(
                title: $title,
                year: $year,
                provider: 'openalex',
                providerId: $providerId,
                externalIds: $externalIds,
                abstract: $abstract,
                authors: $authors,
                venue: $venue,
                url: $url,
                citedByCount: $citations,
                rawData: $raw
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAuthors(array $raw): array
    {
        $authorships = $raw['authorships'] ?? [];
        $authors = [];

        foreach ($authorships as $authorship) {
            $authorData = $authorship['author'] ?? [];
            $name = $authorData['display_name'] ?? 'Unknown';

            if (empty(trim($name))) {
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

            $orcid = $authorData['orcid'] ?? null;
            if ($orcid && str_contains($orcid, 'orcid.org/')) {
                $parts = explode('/', $orcid);
                $orcid = end($parts);
            }

            $authors[] = new Author(familyName: $family, givenName: $given, orcid: $orcid);
        }

        return $authors;
    }

    private function extractIds(array $raw): ExternalIds
    {
        $extractor = new FieldExtractor($raw);
        $doi = $extractor->getString('doi');
        if ($doi && str_contains($doi, 'doi.org/')) {
            $parts = explode('doi.org/', $doi);
            $doi = end($parts);
        }

        $openalexId = $extractor->getString('id');
        if ($openalexId && str_contains($openalexId, 'openalex.org/')) {
            $parts = explode('/', $openalexId);
            $openalexId = end($parts);
        }

        $pmid = $extractor->getString('ids.pmid');
        if ($pmid) {
            $pmid = str_replace('https://pubmed.ncbi.nlm.nih.gov/', '', $pmid);
        }

        return new ExternalIds(
            doi: $doi,
            openalexId: $openalexId,
            pubmedId: $pmid
        );
    }

    private function extractAbstract(array $raw): ?string
    {
        $invertedIndex = $raw['abstract_inverted_index'] ?? null;
        if (!$invertedIndex) {
            return null;
        }

        try {
            $wordPositions = [];
            foreach ($invertedIndex as $word => $positions) {
                foreach ($positions as $pos) {
                    $wordPositions[$pos] = $word;
                }
            }

            ksort($wordPositions);
            $abstract = implode(' ', $wordPositions);

            return mb_substr($abstract, 0, 5000);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCitingWorks(string $openalexId, int $limit = 100): Generator
    {
        $params = [
            'filter' => "cites:W{$openalexId}",
            'per-page' => min(200, $limit),
            'select' => 'id,doi,display_name,title,publication_year,publication_date,primary_location,authorships,cited_by_count,biblio,is_retracted,type,open_access,abstract_inverted_index',
        ];

        if ($this->config->mailto) {
            $params['mailto'] = $this->config->mailto;
        }

        $totalRetrieved = 0;

        while ($totalRetrieved < $limit) {
            $response = $this->makeRequest(self::BASE_URL, $params);
            
            $results = $response['results'] ?? [];
            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
                if ($totalRetrieved >= $limit) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc) {
                    yield $doc;
                    $totalRetrieved++;
                }
            }

            $nextCursor = $response['meta']['next_cursor'] ?? null;
            if (!$nextCursor || $nextCursor === ($params['cursor'] ?? null)) {
                break;
            }

            $params['cursor'] = $nextCursor;
        }
    }

    public function getReferencedWorks(string $openalexId, int $limit = 50): Generator
    {
        $params = [
            'filter' => "referenced_works:W{$openalexId}",
            'per-page' => min(200, $limit),
            'select' => 'id,doi,display_name,title,publication_year,publication_date,primary_location,authorships,cited_by_count,biblio,is_retracted,type,open_access,abstract_inverted_index',
        ];

        if ($this->config->mailto) {
            $params['mailto'] = $this->config->mailto;
        }

        $totalRetrieved = 0;

        while ($totalRetrieved < $limit) {
            $response = $this->makeRequest(self::BASE_URL, $params);
            
            $results = $response['results'] ?? [];
            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
                if ($totalRetrieved >= $limit) {
                    return;
                }

                $doc = $this->normalizeResponse($item);
                if ($doc) {
                    yield $doc;
                    $totalRetrieved++;
                }
            }

            $nextCursor = $response['meta']['next_cursor'] ?? null;
            if (!$nextCursor || $nextCursor === ($params['cursor'] ?? null)) {
                break;
            }

            $params['cursor'] = $nextCursor;
        }
    }

    public function getCitingDocuments(Document $document, int $limit = 100): Generator
    {
        $openalexId = $document->externalIds->openalexId;
        if ($openalexId) {
            yield from $this->getCitingWorks($openalexId, $limit);
        }
    }

    public function getReferencedDocuments(Document $document, int $limit = 50): Generator
    {
        $openalexId = $document->externalIds->openalexId;
        if ($openalexId) {
            yield from $this->getReferencedWorks($openalexId, $limit);
        }
    }

    public function getWorkById(string $id): ?Document
    {
        $params = [
            'select' => 'id,doi,display_name,title,publication_year,publication_date,primary_location,authorships,cited_by_count,biblio,is_retracted,type,open_access,abstract_inverted_index,referenced_works,cited_by_count',
        ];

        if ($this->config->mailto) {
            $params['mailto'] = $this->config->mailto;
        }

        try {
            $url = self::BASE_URL . '/' . $id;
            $response = $this->makeRequest($url, $params);
            return $this->normalizeResponse($response);
        } catch (\Exception $e) {
            return null;
        }
    }
}
