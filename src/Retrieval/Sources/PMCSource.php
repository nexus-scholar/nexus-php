<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class PMCSource extends BaseSource
{
    public function getName(): string
    {
        return 'pmc';
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
        $pmcId = $doc->externalIds->pubmedId;
        if (!$pmcId) {
            return null;
        }

        try {
            $url = "https://www.ncbi.nlm.nih.gov/pmc/utils/idconv/v1.0/?ids={$pmcId}&format=json";
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            $records = $data['records'] ?? [];
            if (empty($records)) {
                return null;
            }

            $record = $records[0];
            $pmcid = $record['pmcid'] ?? null;
            
            if ($pmcid) {
                return "https://www.ncbi.nlm.nih.gov/pmc/articles/{$pmcid}/pdf";
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
