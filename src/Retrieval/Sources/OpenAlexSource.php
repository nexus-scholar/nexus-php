<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class OpenAlexSource extends BaseSource
{
    public function getName(): string
    {
        return 'openalex';
    }

    public function fetch(Document $doc, string $outputPath): bool
    {
        $doi = $doc->externalIds->doi;
        if (! $doi) {
            return false;
        }

        $url = "https://api.openalex.org/works/https://doi.org/{$doi}";

        if ($this->email) {
            $url .= '?mailto='.urlencode($this->email);
        }

        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            $oaData = $data['open_access'] ?? [];
            $oaUrl = $oaData['oa_url'] ?? null;

            if (! $oaUrl) {
                $bestLocation = $data['best_oa_location'] ?? null;
                $oaUrl = $bestLocation['pdf_url'] ?? null;
            }

            if (! $oaUrl) {
                return false;
            }

            return $this->downloadFile($oaUrl, $outputPath);
        } catch (\Exception $e) {
            return false;
        }
    }
}
