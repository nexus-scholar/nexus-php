<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class CORESource extends BaseSource
{
    public function getName(): string
    {
        return 'core';
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

        try {
            $url = "https://api.core.ac.uk/v3/search/doi:" . urlencode($doi);
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . ($this->email ?? ''),
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            $results = $data['results'] ?? [];
            if (empty($results)) {
                return null;
            }

            foreach ($results as $result) {
                if (isset($result['downloadUrl']) && $result['downloadUrl']) {
                    return $result['downloadUrl'];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
