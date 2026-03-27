<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Utils\Exceptions\ExportError;

class RisExporter extends BaseExporter
{
    public function getFileExtension(): string
    {
        return 'ris';
    }

    public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        try {
            $content = '';

            foreach ($documents as $doc) {
                $content .= $this->documentToRis($doc) . "\n\n";
            }

            $this->writeToFile($path, $content);
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write RIS file: {$e->getMessage()}", 'ris');
        }

        return $path;
    }

    public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string
    {
        $representatives = array_map(
            fn(DocumentCluster $c) => $c->representative,
            $clusters
        );

        return $this->exportDocuments($representatives, $outputFile, ...$kwargs);
    }

    private function documentToRis(Document $doc): string
    {
        $lines = [];
        $ty = $this->determineRisType($doc);
        $lines[] = "TY  - {$ty}";

        if ($doc->title) {
            $lines[] = "TI  - {$doc->title}";
        }

        foreach ($doc->authors as $author) {
            if ($author->familyName) {
                if ($author->givenName) {
                    $lines[] = "AU  - {$author->familyName}, {$author->givenName}";
                } else {
                    $lines[] = "AU  - {$author->familyName}";
                }
            } else {
                $lines[] = "AU  - {$author->getFullName()}";
            }
        }

        if ($doc->year) {
            $lines[] = "PY  - {$doc->year}";
        }

        if ($doc->venue) {
            if ($ty === 'JOUR') {
                $lines[] = "JO  - {$doc->venue}";
            } else {
                $lines[] = "T2  - {$doc->venue}";
            }
        }

        if ($doc->abstract) {
            $lines[] = "AB  - {$doc->abstract}";
        }

        if ($doc->externalIds->doi) {
            $lines[] = "DO  - {$doc->externalIds->doi}";
        }

        if ($doc->url) {
            $lines[] = "UR  - {$doc->url}";
        }

        if ($doc->provider) {
            $lines[] = "DB  - {$doc->provider}";
        }

        if ($doc->externalIds->arxivId) {
            $lines[] = "C1  - arXiv: {$doc->externalIds->arxivId}";
        }

        $lines[] = "ER  -";

        return implode("\n", $lines);
    }

    private function determineRisType(Document $doc): string
    {
        $venueLower = strtolower($doc->venue ?? '');

        if (str_contains($venueLower, 'journal')
            || str_contains($venueLower, 'review')
            || str_contains($venueLower, 'transaction')
        ) {
            return 'JOUR';
        }

        if (str_contains($venueLower, 'conference')
            || str_contains($venueLower, 'proceedings')
            || str_contains($venueLower, 'symposium')
        ) {
            return 'CONF';
        }

        if ($doc->externalIds->doi) {
            return 'JOUR';
        }

        return 'GEN';
    }
}
