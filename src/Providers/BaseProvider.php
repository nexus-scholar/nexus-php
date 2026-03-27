<?php

namespace Nexus\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nexus\Models\Document;
use Nexus\Models\ProviderConfig;
use Nexus\Models\Query;
use Generator;

abstract class BaseProvider
{
    protected Client $client;
    protected ?string $lastQuery = null;

    public function __construct(
        protected ProviderConfig $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? $this->createDefaultClient();
    }

    private function createDefaultClient(): Client
    {
        $options = [
            'timeout' => $this->config->timeout,
        ];

        $certPath = $this->findCACertPath();
        if ($certPath) {
            $options['verify'] = $certPath;
        }

        return new Client($options);
    }

    private function findCACertPath(): ?string
    {
        $paths = [
            __DIR__ . '/../../cacert.pem',
            __DIR__ . '/../../../cacert.pem',
            getcwd() . '/cacert.pem',
        ];

        foreach ($paths as $path) {
            $realPath = realpath($path);
            if ($realPath !== false && file_exists($realPath)) {
                return $realPath;
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->config->name;
    }

    public function getLastQuery(): ?string
    {
        return $this->lastQuery;
    }

    /**
     * @return Generator<Document>
     */
    abstract public function search(Query $query): Generator;

    abstract protected function translateQuery(Query $query): array;

    abstract protected function normalizeResponse(mixed $raw): ?Document;

    /**
     * @throws GuzzleException
     */
    protected function makeRequest(string $url, array $params = [], array $headers = []): array
    {
        $queryString = http_build_query($params);
        $this->lastQuery = $url . ($queryString ? '?' . $queryString : '');

        $defaultHeaders = [
            'User-Agent' => 'NexusPHP/1.0' . ($this->config->mailto ? ' (' . $this->config->mailto . ')' : ''),
        ];

        $response = $this->client->get($url, [
            'query' => $params,
            'headers' => array_merge($defaultHeaders, $headers),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
