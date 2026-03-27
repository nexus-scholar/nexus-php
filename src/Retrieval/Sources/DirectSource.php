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
        if (!$doc->url) {
            return false;
        }

        $url = strtolower($doc->url);
        if (!str_ends_with($url, '.pdf')) {
            return false;
        }

        return $this->downloadFile($doc->url, $outputPath);
    }
}
