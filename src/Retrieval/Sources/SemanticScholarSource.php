<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class SemanticScholarSource extends BaseSource
{
    public function getName(): string
    {
        return 'semantic_scholar';
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
        $s2Id = $doc->externalIds->s2Id;
        if (!$s2Id) {
            return null;
        }

        try {
            $url = "https://api.semanticscholar.org/graph/v1/paper/{$s2Id}?fields=pdfUrl";
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['pdfUrl'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
