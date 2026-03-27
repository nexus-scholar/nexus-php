<?php

namespace Nexus\Retrieval\Sources;

use GuzzleHttp\Client;
use Nexus\Models\Document;

class UnpaywallSource extends BaseSource
{
    public function getName(): string
    {
        return 'unpaywall';
    }

    public function fetch(Document $doc, string $outputPath): bool
    {
        $pdfUrl = $this->getPdfUrl($doc);
        if (!$pdfUrl) {
            return false;
        }

        return $this->downloadFile($pdfUrl, $outputPath);
    }

    public function getPdfUrl(Document $doc): ?string
    {
        $doi = $doc->externalIds->doi;
        if (!$doi) {
            return null;
        }

        $email = $this->email ?? 'pdf@example.com';
        $url = "https://api.unpaywall.org/v2/{$doi}?email={$email}";

        try {
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            $bestLocation = $data['best_oa_location'] ?? null;
            if (!$bestLocation) {
                return null;
            }

            return $bestLocation['url_for_pdf'] ?? $bestLocation['url'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
