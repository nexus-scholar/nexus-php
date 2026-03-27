<?php

namespace Nexus\Retrieval;

use Nexus\Models\Document;
use Nexus\Retrieval\Sources\ArxivSource;
use Nexus\Retrieval\Sources\DirectSource;
use Nexus\Retrieval\Sources\OpenAlexSource;
use Nexus\Retrieval\Sources\UnpaywallSource;

class PDFFetcher
{
    private array $sources = [];
    private string $outputDir;

    public function __construct(
        string $outputDir,
        ?string $email = null,
        ?array $enabledSources = null
    ) {
        $this->outputDir = $outputDir;
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        $defaultSources = [
            'direct' => true,
            'arxiv' => true,
            'unpaywall' => true,
            'openalex' => true,
        ];

        $sourcesToEnable = $enabledSources ?? $defaultSources;

        if ($sourcesToEnable['direct'] ?? true) {
            $this->sources[] = new DirectSource($email);
        }

        if ($sourcesToEnable['arxiv'] ?? true) {
            $this->sources[] = new ArxivSource($email);
        }

        if ($sourcesToEnable['unpaywall'] ?? true) {
            $this->sources[] = new UnpaywallSource($email);
        }

        if ($sourcesToEnable['openalex'] ?? true) {
            $this->sources[] = new OpenAlexSource($email);
        }
    }

    public function fetch(Document $doc): string|false
    {
        $filename = $this->getFilename($doc);
        $outputPath = $this->outputDir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($outputPath)) {
            return $outputPath;
        }

        foreach ($this->sources as $source) {
            try {
                if ($source->fetch($doc, $outputPath)) {
                    if (file_exists($outputPath)) {
                        return $outputPath;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }

    public function fetchBatch(array $documents): array
    {
        $results = [];
        
        foreach ($documents as $document) {
            $results[$document->providerId] = $this->fetch($document);
        }

        return $results;
    }

    public function getFilename(Document $doc): string
    {
        if ($doc->externalIds->doi) {
            $safeDoi = str_replace(['/', ':'], '_', $doc->externalIds->doi);
            return $safeDoi . '.pdf';
        }

        if ($doc->externalIds->arxivId) {
            return 'arxiv_' . $doc->externalIds->arxivId . '.pdf';
        }

        return 'doc_' . abs(crc32($doc->title)) . '.pdf';
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public function checkAvailability(Document $doc): bool
    {
        return $this->getPdfUrl($doc) !== null;
    }

    public function getPdfUrl(Document $doc): ?string
    {
        foreach ($this->sources as $source) {
            try {
                $url = $source->getPdfUrl($doc);
                if ($url) {
                    return $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function getAllPdfUrls(Document $doc): array
    {
        $urls = [];

        foreach ($this->sources as $source) {
            try {
                $url = $source->getPdfUrl($doc);
                if ($url) {
                    $urls[$source->getName()] = $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $urls;
    }

    public function checkBatchAvailability(array $documents): array
    {
        $results = [];

        foreach ($documents as $document) {
            $results[$document->providerId] = $this->checkAvailability($document);
        }

        return $results;
    }
}
