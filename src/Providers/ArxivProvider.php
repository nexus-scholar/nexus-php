<?php

namespace Nexus\Providers;

use Generator;
use Nexus\Models\Author;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Models\QueryField;
use Nexus\Utils\BooleanQueryTranslator;
use SimpleXMLElement;

class ArxivProvider extends BaseProvider
{
    private const string BASE_URL = 'https://export.arxiv.org/api/query';

    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);

        $fieldMap = [
            QueryField::TITLE->value => 'ti',
            QueryField::ABSTRACT->value => 'abs',
            QueryField::AUTHOR->value => 'au',
            QueryField::VENUE->value => 'jr',
            QueryField::ANY->value => 'all',
        ];

        $this->translator = new BooleanQueryTranslator($fieldMap);
    }

    public function search(Query $query): Generator
    {
        $params = $this->translateQuery($query);

        $start = 0;
        $maxResultsPerPage = 100;
        $totalFetched = 0;
        $seenArxivIds = [];
        $maxResults = $query->maxResults ?? PHP_INT_MAX;

        while ($totalFetched < $maxResults && $start < 10000) {
            $params['start'] = $start;
            $params['max_results'] = $maxResultsPerPage;

            $response = $this->makeRawRequest(self::BASE_URL, $params);
            $xmlContent = $response->getBody()->getContents();

            try {
                $xml = new SimpleXMLElement($xmlContent);
            } catch (\Exception $e) {
                break;
            }

            $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
            $xml->registerXPathNamespace('arxiv', 'http://arxiv.org/schemas/atom');
            $entries = $xml->xpath('//atom:entry');

            if (empty($entries)) {
                break;
            }

            foreach ($entries as $entry) {
                if ($totalFetched >= $maxResults) {
                    return;
                }

                $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $entry->registerXPathNamespace('arxiv', 'http://arxiv.org/schemas/atom');

                $doc = $this->normalizeResponse(['entry' => $entry]);
                if ($doc === null) {
                    continue;
                }

                $arxivId = $doc->externalIds->arxivId;
                if ($arxivId && isset($seenArxivIds[$arxivId])) {
                    continue;
                }

                if ($arxivId) {
                    $seenArxivIds[$arxivId] = true;
                }

                if ($this->passesFilters($doc, $query)) {
                    $doc->queryId = $query->id;
                    $doc->queryText = $query->text;
                    yield $doc;
                    $totalFetched++;
                }
            }

            if (count($entries) < $maxResultsPerPage) {
                break;
            }

            $start += count($entries);
        }
    }

    protected function translateQuery(Query $query): array
    {
        $translation = $this->translator->translate($query);

        return [
            'search_query' => $translation['q'],
            'sortBy' => 'submittedDate',
            'sortOrder' => 'descending',
        ];
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        $entry = $raw['entry'] ?? null;
        if (! ($entry instanceof SimpleXMLElement)) {
            return null;
        }

        $title = $this->xpathString($entry, 'atom:title');
        if (! $title) {
            return null;
        }

        $abstract = $this->xpathString($entry, 'atom:summary');
        $published = $this->xpathString($entry, 'atom:published');
        $year = $this->extractYear($published);

        $idUrl = $this->xpathString($entry, 'atom:id');
        $arxivId = $this->extractArxivId($idUrl);

        $doi = $this->xpathString($entry, 'arxiv:doi');

        $authors = $this->parseAuthors($entry);

        $primaryCategory = $this->xpathAttribute($entry, 'arxiv:primary_category', 'term');
        $venue = $primaryCategory ? "arXiv ($primaryCategory)" : 'arXiv';

        $externalIds = new ExternalIds(
            doi: $doi,
            arxivId: $arxivId
        );

        return new Document(
            title: trim($title ?? ''),
            year: $year,
            provider: 'arxiv',
            providerId: $arxivId ?? substr(md5($title ?? ''), 0, 16),
            externalIds: $externalIds,
            abstract: $abstract !== null ? trim($abstract) : null,
            authors: $authors,
            venue: $venue,
            url: $idUrl,
            rawData: null
        );
    }

    private function xpathString(SimpleXMLElement $xml, string $path): ?string
    {
        $result = $xml->xpath($path);
        if ($result === false || ! isset($result[0])) {
            return null;
        }

        $value = (string) $result[0];

        return $value !== '' ? trim($value) : null;
    }

    private function xpathAttribute(SimpleXMLElement $xml, string $path, string $attr): ?string
    {
        $result = $xml->xpath($path);
        if ($result === false || ! isset($result[0])) {
            return null;
        }

        $element = $result[0];
        if (! is_object($element)) {
            return null;
        }

        $value = (string) ($element[$attr] ?? '');

        return $value !== '' ? $value : null;
    }

    private function parseAuthors(SimpleXMLElement $entry): array
    {
        $authors = [];
        $authorNodes = $entry->xpath('atom:author');

        if (! is_array($authorNodes)) {
            return $authors;
        }

        foreach ($authorNodes as $authorNode) {
            if (! $authorNode instanceof SimpleXMLElement) {
                continue;
            }

            $authorNode->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
            $authorNode->registerXPathNamespace('arxiv', 'http://arxiv.org/schemas/atom');

            $nameResult = $authorNode->xpath('atom:name');
            if (! is_array($nameResult) || ! isset($nameResult[0])) {
                continue;
            }

            $nameValue = (string) $nameResult[0];
            $name = $nameValue !== '' ? trim($nameValue) : '';
            if (! $name) {
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

    private function extractYear(?string $dateStr): ?int
    {
        if (! $dateStr) {
            return null;
        }

        $year = (int) substr($dateStr, 0, 4);

        return ($year >= 1900 && $year <= 2100) ? $year : null;
    }

    private function extractArxivId(?string $idUrl): ?string
    {
        if (! $idUrl) {
            return null;
        }

        if (preg_match('/arxiv\.org\/abs\/(\d+\.\d+)(?:v\d+)?/i', $idUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function passesFilters(Document $doc, Query $query): bool
    {
        if ($doc->year) {
            if ($query->yearMin && $doc->year < $query->yearMin) {
                return false;
            }
            if ($query->yearMax && $doc->year > $query->yearMax) {
                return false;
            }
        }

        return true;
    }
}
