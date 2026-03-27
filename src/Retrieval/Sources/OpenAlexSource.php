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
        $pdfUrls = $this->getPdfUrls($doc);
        
        foreach ($pdfUrls as $url) {
            if ($this->downloadFile($url, $outputPath)) {
                return true;
            }
        }
        
        return false;
    }

    public function getPdfUrl(Document $doc): ?string
    {
        $urls = $this->getPdfUrls($doc);
        return $urls[0] ?? null;
    }

    public function getPdfUrls(Document $doc): array
    {
        $doi = $doc->externalIds->doi;
        if (!$doi) {
            return [];
        }

        $url = "https://api.openalex.org/works/https://doi.org/{$doi}";

        if ($this->email) {
            $url .= '?mailto=' . urlencode($this->email);
        }

        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = json_decode($response->getBody()->getContents(), true);
            $pdfUrls = [];

            $bestLocation = $data['best_oa_location'] ?? null;
            if ($bestLocation) {
                if (!empty($bestLocation['pdf_url'])) {
                    $pdfUrls[] = $bestLocation['pdf_url'];
                }
                if (!empty($bestLocation['url_for_pdf'])) {
                    $pdfUrls[] = $bestLocation['url_for_pdf'];
                }
            }

            $locations = $data['locations'] ?? [];
            foreach ($locations as $location) {
                if (!empty($location['pdf_url'])) {
                    $pdfUrl = $location['pdf_url'];
                    if (!in_array($pdfUrl, $pdfUrls)) {
                        $pdfUrls[] = $pdfUrl;
                    }
                }
                if (!empty($location['url_for_pdf'])) {
                    $urlForPdf = $location['url_for_pdf'];
                    if (!in_array($urlForPdf, $pdfUrls)) {
                        $pdfUrls[] = $urlForPdf;
                    }
                }
            }

            return $pdfUrls;
        } catch (\Exception $e) {
            return [];
        }
    }
}
