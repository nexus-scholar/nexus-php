<?php

namespace Nexus\Providers;

use Generator;
use Nexus\Models\Author;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Models\QueryField;
use Nexus\Utils\BooleanQueryTranslator;
use Nexus\Utils\Exceptions\NetworkError;
use Nexus\Utils\Exceptions\RateLimitError;
use SimpleXMLElement;

class PubMedProvider extends BaseProvider
{
    private const BASE_URL = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils';

    private BooleanQueryTranslator $translator;

    public function __construct($config, $client = null)
    {
        parent::__construct($config, $client);

        if ($config->rateLimit == 1.0) {
            $config->rateLimit = $config->apiKey ? 10.0 : 3.0;
        }

        $fieldMap = [
            QueryField::TITLE->value => 'Title',
            QueryField::ABSTRACT->value => 'Abstract',
            QueryField::AUTHOR->value => 'Author',
            QueryField::VENUE->value => 'Journal',
            QueryField::YEAR->value => 'Date - Publication',
            QueryField::DOI->value => 'DOI',
        ];

        $this->translator = new BooleanQueryTranslator($fieldMap);

        $translator = $this->translator;
        $this->translator = new class($fieldMap, $translator) extends BooleanQueryTranslator {
            public function __construct(
                array $fieldMap,
                private BooleanQueryTranslator $original
            ) {
                parent::__construct($fieldMap);
            }

            public function formatFieldTerm(string $field, string $term, bool $isPhrase): string
            {
                if (!$field || $field === 'any') {
                    return $isPhrase ? "\"{$term}\"" : $term;
                }

                $val = $isPhrase ? "\"{$term}\"" : $term;

                return "{$val}[{$field}]";
            }
        };
    }

    public function search(Query $query): Generator
    {
        $esearchParams = $this->translateQuery($query);
        $maxResults = $query->maxResults ?? 1000;
        $esearchParams['retmax'] = min($maxResults, 10000);
        $esearchParams['usehistory'] = 'y';

        try {
            $esearchUrl = self::BASE_URL . '/esearch.fcgi';
            $esearchResponse = $this->makeRequestXml($esearchUrl, $esearchParams);
            $esearchRoot = simplexml_load_string($esearchResponse);

            if ($esearchRoot === false) {
                return;
            }

            $errorList = $esearchRoot->ErrorList;
            if ($errorList !== null) {
                $phraseNotFound = $errorList->PhraseNotFound;
                if ($phraseNotFound !== null) {
                    return;
                }
            }

            $webenv = (string) ($esearchRoot->WebEnv ?? '');
            $queryKey = (string) ($esearchRoot->QueryKey ?? '');
            $countText = (string) ($esearchRoot->Count ?? '0');
            $count = $countText ? (int) $countText : 0;

            $idList = [];
            foreach ($esearchRoot->IdList->Id as $idElem) {
                $idList[] = (string) $idElem;
            }

            if ($count === 0) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $batchSize = 200;
        $totalFetched = 0;

        if ($webenv && $queryKey) {
            for ($start = 0; $start < min($count, $maxResults); $start += $batchSize) {
                $efetchParams = [
                    'db' => 'pubmed',
                    'query_key' => $queryKey,
                    'WebEnv' => $webenv,
                    'retstart' => $start,
                    'retmax' => $batchSize,
                    'retmode' => 'xml',
                ];
                if ($this->config->apiKey) {
                    $efetchParams['api_key'] = $this->config->apiKey;
                }

                yield from $this->fetchAndProcessBatch($efetchParams);
            }
        } else {
            for ($i = 0; $i < count($idList); $i += $batchSize) {
                if ($totalFetched >= $maxResults) {
                    break;
                }
                $batchIds = array_slice($idList, $i, $batchSize);
                $efetchParams = [
                    'db' => 'pubmed',
                    'id' => implode(',', $batchIds),
                    'retmode' => 'xml',
                ];
                if ($this->config->apiKey) {
                    $efetchParams['api_key'] = $this->config->apiKey;
                }

                yield from $this->fetchAndProcessBatch($efetchParams);
                $totalFetched += count($batchIds);
            }
        }
    }

    private function fetchAndProcessBatch(array $params): Generator
    {
        $efetchUrl = self::BASE_URL . '/efetch.fcgi';
        try {
            $responseXml = $this->makeRequestXml($efetchUrl, $params);
            $root = simplexml_load_string($responseXml);

            if ($root === false) {
                return;
            }

            foreach ($root->PubmedArticle as $article) {
                $doc = $this->normalizeResponse($article);
                if ($doc) {
                    yield $doc;
                }
            }
        } catch (\Throwable $e) {
        }
    }

    protected function translateQuery(Query $query): array
    {
        $params = [
            'db' => 'pubmed',
            'term' => $query->text,
            'retmode' => 'xml',
        ];

        $dateQuery = '';
        if ($query->yearMin && $query->yearMax) {
            $dateQuery = " AND {$query->yearMin}:{$query->yearMax}[Date - Publication]";
        } elseif ($query->yearMin) {
            $dateQuery = " AND {$query->yearMin}:3000[Date - Publication]";
        } elseif ($query->yearMax) {
            $dateQuery = " AND 1000:{$query->yearMax}[Date - Publication]";
        }

        if ($dateQuery) {
            $params['term'] = "({$params['term']}){$dateQuery}";
        }

        if ($this->config->apiKey) {
            $params['api_key'] = $this->config->apiKey;
        }

        return $params;
    }

    protected function normalizeResponse(mixed $raw): ?Document
    {
        try {
            $medlineCitation = $raw->MedlineCitation ?? null;
            $article = $medlineCitation?->Article ?? null;
            if ($article === null) {
                return null;
            }

            $title = (string) ($article->ArticleTitle ?? '');
            if (!$title) {
                return null;
            }

            $abstract = '';
            $abstractElem = $article->Abstract ?? null;
            if ($abstractElem !== null) {
                $texts = $abstractElem->AbstractText ?? [];
                $abstractParts = [];
                foreach ($texts as $text) {
                    $textContent = (string) $text;
                    if ($textContent !== '') {
                        $abstractParts[] = $textContent;
                    }
                }
                $abstract = implode(' ', $abstractParts);
            }

            $authors = [];
            $authorList = $article->AuthorList ?? null;
            if ($authorList !== null) {
                foreach ($authorList->Author as $au) {
                    $last = (string) ($au->LastName ?? '');
                    $fore = (string) ($au->ForeName ?? '');
                    $orcid = null;

                    foreach ($au->Identifier as $idNode) {
                        $source = (string) ($idNode['Source'] ?? '');
                        if ($source === 'ORCID') {
                            $orcidText = (string) $idNode;
                            if ($orcidText !== '' && str_contains($orcidText, 'orcid.org/')) {
                                $orcid = explode('orcid.org/', $orcidText)[1] ?? null;
                            } else {
                                $orcid = $orcidText !== '' ? $orcidText : null;
                            }
                        }
                    }

                    if ($last !== '') {
                        $authors[] = new Author(
                            familyName: $last,
                            givenName: $fore !== '' ? $fore : null,
                            orcid: $orcid
                        );
                    }
                }
            }

            $year = null;
            $pubDate = $article->Journal?->JournalIssue?->PubDate ?? null;
            if ($pubDate !== null) {
                $yearText = (string) ($pubDate->Year ?? '');
                if ($yearText !== '') {
                    $year = (int) $yearText;
                } else {
                    $medlineDate = (string) ($pubDate->MedlineDate ?? '');
                    if ($medlineDate !== '') {
                        preg_match('/\d{4}/', $medlineDate, $matches);
                        if (!empty($matches)) {
                            $year = (int) $matches[0];
                        }
                    }
                }
            }

            $venue = (string) ($article->Journal?->Title ?? '');
            $pmid = (string) ($medlineCitation->PMID ?? '');
            $doi = null;

            foreach ($article->ELocationID ?? [] as $eloc) {
                $eIdType = (string) ($eloc['EIdType'] ?? '');
                if ($eIdType === 'doi') {
                    $doiText = (string) $eloc;
                    if ($doiText !== '') {
                        $doi = $doiText;
                        break;
                    }
                }
            }

            if ($doi === null) {
                $articleIds = $raw->PubmedData?->ArticleIdList ?? null;
                if ($articleIds !== null) {
                    foreach ($articleIds->ArticleId as $aid) {
                        $idType = (string) ($aid['IdType'] ?? '');
                        if ($idType === 'doi') {
                            $doiText = (string) $aid;
                            if ($doiText !== '') {
                                $doi = $doiText;
                                break;
                            }
                        }
                    }
                }
            }

            $url = $pmid !== '' ? "https://pubmed.ncbi.nlm.nih.gov/{$pmid}/" : null;
            $externalIds = new ExternalIds(pubmedId: $pmid !== '' ? $pmid : null, doi: $doi);

            return new Document(
                title: $title,
                year: $year,
                provider: 'pubmed',
                providerId: $pmid !== '' ? $pmid : ($doi ?? (string) abs(crc32($title))),
                externalIds: $externalIds,
                abstract: $abstract !== '' ? $abstract : null,
                authors: $authors,
                venue: $venue !== '' ? $venue : null,
                url: $url,
                rawData: null
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function makeRequestXml(string $url, array $params = []): string
    {
        $queryString = http_build_query($params);
        $fullUrl = $url . ($queryString ? '?' . $queryString : '');
        $this->lastQuery = $fullUrl;

        $response = $this->client->get($url, ['query' => $params]);

        return $response->getBody()->getContents();
    }
}
