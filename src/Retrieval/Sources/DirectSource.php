<?php

namespace Nexus\Retrieval\Sources;

use Nexus\Models\Document;

class DirectSource extends BaseSource
{
    public function getName(): string
    {
        return 'direct';
    }

    public function fetch(Document $doc, string $outputPath): bool
    {
        $url = $this->getPdfUrl($doc);
        if (! $url) {
            return false;
        }

        return $this->downloadFile($url, $outputPath);
    }

    public function getPdfUrl(Document $doc): ?string
    {
        if ($doc->url) {
            $url = strtolower($doc->url);
            if (str_ends_with($url, '.pdf')) {
                return $doc->url;
            }
        }

        if ($doc->externalIds->doi) {
            $doiUrl = 'https://doi.org/'.$doc->externalIds->doi;

            return $doiUrl;
        }

        return null;
    }
}
