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
        $arxivId = $doc->externalIds->arxivId;
        if (! $arxivId) {
            return false;
        }

        $url = "https://arxiv.org/pdf/{$arxivId}.pdf";

        return $this->downloadFile($url, $outputPath);
    }
}
