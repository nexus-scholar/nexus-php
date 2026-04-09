<?php

namespace Nexus\Retrieval\Sources;

use GuzzleHttp\Client;
use Nexus\Models\Document;
use Nexus\Retrieval\PDFSourceInterface;

abstract class BaseSource implements PDFSourceInterface
{
    protected Client $client;

    protected const HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'application/pdf,application/octet-stream;q=0.9,*/*;q=0.8',
    ];

    public function __construct(
        protected ?string $email = null
    ) {
        $options = [
            'timeout' => 30,
            'verify' => dirname(__DIR__, 3).'/cacert.pem',
        ];

        $this->client = new Client($options);
    }

    abstract public function getName(): string;

    abstract public function fetch(Document $doc, string $outputPath): bool;

    protected function downloadFile(string $url, string $outputPath, int $retries = 2): bool
    {
        $tempPath = $outputPath.'.tmp';

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $response = $this->client->get($url, [
                    'headers' => self::HEADERS,
                    'allow_redirects' => true,
                ]);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $contentType = $response->getHeaderLine('Content-Type');
                if (stripos($contentType, 'text/html') !== false) {
                    continue;
                }

                $body = $response->getBody()->getContents();

                if (! $this->isValidPdf($body)) {
                    continue;
                }

                file_put_contents($tempPath, $body);

                if (file_exists($tempPath)) {
                    rename($tempPath, $outputPath);

                    return true;
                }
            } catch (\Exception $e) {
                if ($attempt < $retries) {
                    sleep(1);
                }
            }
        }

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        return false;
    }

    protected function isValidPdf(string $content): bool
    {
        if (strlen($content) < 4) {
            return false;
        }

        return substr($content, 0, 4) === '%PDF';
    }
}
