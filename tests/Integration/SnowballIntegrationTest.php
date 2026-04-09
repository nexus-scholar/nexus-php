<?php

namespace Nexus\Tests\Integration;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Nexus\Config\ConfigLoader;
use Nexus\Config\NexusConfig;
use Nexus\Core\ProviderFactory;
use Nexus\Core\SnowballService;
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Export\JsonExporter;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Models\Query;
use Nexus\Models\SnowballConfig;
use PHPUnit\Framework\TestCase;

class SnowballIntegrationTest extends TestCase
{
    private NexusConfig $config;

    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = ConfigLoader::loadDefault();
        $this->fixturesDir = __DIR__.'/../fixtures';
        $this->ensureSSLCertificates();
    }

    private function ensureSSLCertificates(): void
    {
        $certPath = __DIR__.'/../../cacert.pem';
        if (file_exists($certPath)) {
            if (! ini_get('curl.cainfo')) {
                ini_set('curl.cainfo', $certPath);
            }
            if (! ini_get('openssl.cafile')) {
                ini_set('openssl.cafile', $certPath);
            }
        }
    }

    private function assertApiCallSucceeds(callable $test): void
    {
        try {
            $test();
        } catch (RequestException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'SSL') || str_contains($message, 'curl error')) {
                $this->markTestSkipped('SSL certificate verification failed. Run: curl -L -o cacert.pem https://curl.se/ca/cacert.pem');
            }
            if ($e->getCode() === 0) {
                $this->markTestSkipped('Network connectivity issue: '.substr($message, 0, 200));
            }
            throw $e;
        } catch (ConnectException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'SSL') || str_contains($message, 'curl error')) {
                $this->markTestSkipped('SSL certificate verification failed. Run: curl -L -o cacert.pem https://curl.se/ca/cacert.pem');
            }
            throw $e;
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'SSL') || str_contains($message, 'curl error')) {
                $this->markTestSkipped('SSL certificate verification failed. Run: curl -L -o cacert.pem https://curl.se/ca/cacert.pem');
            }
            if (str_contains($message, 'API key')) {
                $this->markTestSkipped('API key not configured: '.$message);
            }
            throw $e;
        }
    }

    public function test_run_queries_and_save_results(): void
    {
        $this->assertApiCallSucceeds(function () {
            $allResults = [];

            $openalex = ProviderFactory::makeFromConfig('openalex', $this->config);
            $queries = [
                new Query(text: 'attention transformer', maxResults: 5, yearMin: 2024),
                new Query(text: 'BERT language model', maxResults: 5, yearMin: 2024),
            ];

            foreach ($queries as $query) {
                $results = iterator_to_array($openalex->search($query));
                foreach ($results as $doc) {
                    $doc->queryText = $query->text;
                }
                $allResults = array_merge($allResults, $results);
            }

            $s2 = ProviderFactory::makeFromConfig('s2', $this->config);
            foreach ($queries as $query) {
                $results = iterator_to_array($s2->search($query));
                foreach ($results as $doc) {
                    $doc->queryText = $query->text;
                }
                $allResults = array_merge($allResults, $results);
            }

            $this->assertNotEmpty($allResults, 'Should return results from at least one provider');

            $exporter = new JsonExporter($this->fixturesDir);
            $outputFile = $exporter->exportDocuments($allResults, 'snowball_input', ['include_raw' => false]);

            $this->assertFileExists($outputFile);

            $loadedData = json_decode(file_get_contents($outputFile), true);
            $this->assertIsArray($loadedData);
        });
    }

    public function test_select_document_and_snowball(): void
    {
        $this->assertApiCallSucceeds(function () {
            $inputFile = $this->fixturesDir.'/snowball_input.json';

            if (! file_exists($inputFile)) {
                $this->markTestSkipped('Run test_run_queries_and_save_results first to create the input file');
            }

            $data = json_decode(file_get_contents($inputFile), true);
            $this->assertIsArray($data);

            $documents = array_map(function ($item) {
                return $this->arrayToDocument($item);
            }, $data);

            usort($documents, function ($a, $b) {
                return ($b->citedByCount ?? 0) <=> ($a->citedByCount ?? 0);
            });

            $seedDocument = $documents[0];
            $this->assertNotNull($seedDocument->externalIds->openalexId, 'Seed document should have an OpenAlex ID for snowballing');

            $snowballConfig = new SnowballConfig(
                forward: true,
                backward: true,
                maxCitations: 20,
                maxReferences: 10,
                depth: 1
            );

            $openalex = ProviderFactory::makeFromConfig('openalex', $this->config);
            $s2 = ProviderFactory::makeFromConfig('s2', $this->config);

            $snowballService = new SnowballService($snowballConfig, $openalex, $s2);

            $uniqueNewDocs = $snowballService->snowball($seedDocument, $documents);

            if (! empty($uniqueNewDocs)) {
                $exporter = new JsonExporter($this->fixturesDir);
                $outputFile = $exporter->exportDocuments($uniqueNewDocs, 'snowball_output', ['include_raw' => false]);

                $this->assertFileExists($outputFile);
            }

            $this->assertIsArray($uniqueNewDocs);
        });
    }

    public function test_snowball_deduplication_against_existing(): void
    {
        $this->assertApiCallSucceeds(function () {
            $inputFile = $this->fixturesDir.'/snowball_input.json';
            $outputFile = $this->fixturesDir.'/snowball_output.json';

            if (! file_exists($inputFile)) {
                $this->markTestSkipped('Run test_run_queries_and_save_results first');
            }

            $existingData = json_decode(file_get_contents($inputFile), true);
            $existingDocs = array_map(function ($item) {
                return $this->arrayToDocument($item);
            }, $existingData);

            if (file_exists($outputFile)) {
                $snowballData = json_decode(file_get_contents($outputFile), true);
                $snowballDocs = array_map(function ($item) {
                    return $this->arrayToDocument($item);
                }, $snowballData);

                $dedupConfig = new DeduplicationConfig(
                    strategy: DeduplicationStrategyName::CONSERVATIVE,
                    fuzzyThreshold: 97,
                    maxYearGap: 1
                );
                $strategy = new ConservativeStrategy($dedupConfig);

                $combinedDocs = array_merge($existingDocs, $snowballDocs);
                $clusters = $strategy->deduplicate($combinedDocs);

                $this->assertNotEmpty($clusters);
            }

            $this->assertNotEmpty($existingDocs);
        });
    }

    private function arrayToDocument(array $data): Document
    {
        $externalIds = new ExternalIds(
            doi: $data['external_ids']['doi'] ?? null,
            arxivId: $data['external_ids']['arxiv_id'] ?? null,
            pubmedId: $data['external_ids']['pubmed_id'] ?? null,
            openalexId: $data['external_ids']['openalex_id'] ?? null,
            s2Id: $data['external_ids']['s2_id'] ?? null
        );

        return new Document(
            title: $data['title'] ?? '',
            year: $data['year'] ?? null,
            provider: $data['provider'] ?? 'unknown',
            providerId: $data['provider_id'] ?? '',
            externalIds: $externalIds,
            abstract: $data['abstract'] ?? null,
            venue: $data['venue'] ?? null,
            url: $data['url'] ?? null,
            citedByCount: $data['cited_by_count'] ?? null,
            queryText: $data['query_text'] ?? null
        );
    }
}
