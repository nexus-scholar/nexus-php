<?php

namespace Nexus\Tests\Integration;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Nexus\Config\ConfigLoader;
use Nexus\Config\NexusConfig;
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Models\Document;
use Nexus\Models\Query;
use PHPUnit\Framework\TestCase;

class RealApiIntegrationTest extends TestCase
{
    private NexusConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = ConfigLoader::loadDefault();
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

    public function test_openalex_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('openalex', $this->config);
            $query = new Query(text: 'machine learning', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            $this->assertNotEmpty($results, 'OpenAlex should return results for "machine learning"');
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_crossref_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('crossref', $this->config);
            $query = new Query(text: 'deep learning', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            $this->assertNotEmpty($results, 'Crossref should return results for "deep learning"');
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_arxiv_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('arxiv', $this->config);
            $query = new Query(text: 'neural network', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            $this->assertNotEmpty($results, 'arXiv should return results for "neural network"');
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_semantic_scholar_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('s2', $this->config);
            $query = new Query(text: 'transformer attention', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            $this->assertNotEmpty($results, 'Semantic Scholar should return results for "transformer attention"');
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_pubmed_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('pubmed', $this->config);
            $query = new Query(text: 'CRISPR gene editing', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            if (empty($results)) {
                $this->markTestSkipped('PubMed API returned no results (may be rate limited or SSL issue)');
            }
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_doaj_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('doaj', $this->config);
            $query = new Query(text: 'climate change', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            if (empty($results)) {
                $this->markTestSkipped('DOAJ API returned no results (may be rate limited or SSL issue)');
            }
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_ieee_real_api(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('ieee', $this->config);
            $query = new Query(text: '5G networks', maxResults: 5);

            $results = iterator_to_array($provider->search($query));

            $this->assertIsArray($results);
            if (empty($results)) {
                $this->markTestSkipped('IEEE API returned no results (may be rate limited, SSL issue, or API key invalid)');
            }
            $this->assertInstanceOf(Document::class, $results[0]);
            $this->assertNotEmpty($results[0]->title);
        });
    }

    public function test_nexus_service_with_config(): void
    {
        $service = new NexusService;
        $providers = [];

        foreach ($this->config->getEnabledProviders() as $name) {
            if ($name !== 'core') {
                $providers[$name] = ProviderFactory::makeFromConfig($name, $this->config);
            }
        }

        foreach ($providers as $provider) {
            $service->registerProvider($provider);
        }

        $query = new Query(text: 'artificial intelligence', maxResults: 3);

        $this->assertApiCallSucceeds(function () use ($service, $query) {
            $allResults = iterator_to_array($service->search($query));

            $this->assertIsArray($allResults);
            $this->assertNotEmpty($allResults, 'NexusService should return results from at least one provider');
        });
    }

    public function test_year_filter_in_query(): void
    {
        $this->assertApiCallSucceeds(function () {
            $provider = ProviderFactory::makeFromConfig('openalex', $this->config);
            $query = new Query(text: 'machine learning', maxResults: 10, yearMin: 2024, yearMax: 2026);

            $results = iterator_to_array($provider->search($query));

            foreach ($results as $result) {
                if ($result->year !== null) {
                    $this->assertGreaterThanOrEqual(2024, $result->year);
                    $this->assertLessThanOrEqual(2026, $result->year);
                }
            }
        });
    }

    public function test_config_to_json(): void
    {
        $data = [
            'mailto' => 'test@example.com',
            'year_min' => 2024,
            'year_max' => 2026,
            'language' => 'en',
            'providers' => [
                'openalex' => ['enabled' => true, 'rate_limit' => 5.0],
            ],
        ];

        $config = ConfigLoader::loadFromArray($data);
        $json = json_encode($config, JSON_PRETTY_PRINT);
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('test@example.com', $decoded['mailto']);
        $this->assertEquals(2024, $decoded['yearMin']);
        $this->assertEquals(2026, $decoded['yearMax']);
    }

    public function test_provider_config_options(): void
    {
        $provider = ProviderFactory::make('openalex', [
            'enabled' => true,
            'rate_limit' => 10.0,
            'timeout' => 60,
            'api_key' => 'test_key',
            'mailto' => 'test@example.com',
        ]);

        $this->assertEquals('openalex', $provider->getName());
    }

    public function test_disabled_provider_returns_empty(): void
    {
        $config = ConfigLoader::loadFromJson(json_encode([
            'mailto' => 'test@example.com',
            'providers' => [
                'openalex' => [
                    'enabled' => false,
                    'rate_limit' => 1.0,
                ],
            ],
        ]));

        $this->assertFalse($config->isProviderEnabled('openalex'));
        $this->assertEmpty($config->getEnabledProviders());
    }
}
