<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class ArxivSource extends BaseSource
{
    public function getName(): string
    {
        return 'arxiv';
    }

    public function fetch(Document $doc, string $outputPath): bool
    {
        $url = $this->getPdfUrl($doc);
        if (!$url) {
            return false;
        }

        return $this->downloadFile($url, $outputPath);
    }

    public function getPdfUrl(Document $doc): ?string
    {
        $arxivId = $doc->externalIds->arxivId;
        if (!$arxivId) {
            return null;
        }

        return "https://arxiv.org/pdf/{$arxivId}.pdf";
    }
}
