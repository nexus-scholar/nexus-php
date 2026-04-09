<?php

namespace Nexus\Retrieval;

use Nexus\Models\Document;
use Nexus\Retrieval\Sources\ArxivSource;
use Nexus\Retrieval\Sources\DirectSource;
use Nexus\Retrieval\Sources\OpenAlexSource;
use Nexus\Retrieval\Sources\SemanticScholarSource;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PDFFetcher
{
    /** @var PDFSourceInterface[] */
    private array $sources = [];

    private string $outputDir;

    private LoggerInterface $logger;

    public function __construct(
        string $outputDir,
        ?string $email = null,
        ?array $enabledSources = null,
        ?LoggerInterface $logger = null,
        ?array $injectedSources = null
    ) {
        $this->outputDir = $outputDir;
        $this->logger = $logger ?? new NullLogger;

        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        if ($injectedSources !== null) {
            $this->sources = $injectedSources;
        } else {
            $defaultSources = [
                'direct' => true,
                'arxiv' => true,
                'openalex' => true,
                'semantic_scholar' => true,
            ];

            $sourcesToEnable = $enabledSources ?? $defaultSources;

            if ($sourcesToEnable['direct'] ?? true) {
                $this->sources[] = new DirectSource($email);
            }

            if ($sourcesToEnable['arxiv'] ?? true) {
                $this->sources[] = new ArxivSource($email);
            }

            if ($sourcesToEnable['semantic_scholar'] ?? true) {
                $this->sources[] = new SemanticScholarSource($email);
            }

            if ($sourcesToEnable['openalex'] ?? true) {
                $this->sources[] = new OpenAlexSource($email);
            }
        }
    }

    public function fetch(Document $doc): string|false
    {
        $filename = $this->getFilename($doc);
        $outputPath = $this->outputDir.DIRECTORY_SEPARATOR.$filename;

        if (file_exists($outputPath)) {
            $this->logger->info('PDF already exists: {path}', ['path' => $outputPath]);

            return $outputPath;
        }

        foreach ($this->sources as $source) {
            try {
                $this->logger->debug('Attempting to fetch PDF from {source} for {title}', [
                    'source' => $source->getName(),
                    'title' => $doc->title,
                ]);

                if ($source->fetch($doc, $outputPath)) {
                    $this->logger->info('Successfully fetched PDF from {source} to {path}', [
                        'source' => $source->getName(),
                        'path' => $outputPath,
                    ]);

                    return $outputPath;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error fetching from {source}: {message}', [
                    'source' => $source->getName(),
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);

                continue;
            }
        }

        $this->logger->warning('Failed to fetch PDF for {title} from all sources', ['title' => $doc->title]);

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

            return $safeDoi.'.pdf';
        }

        if ($doc->externalIds->arxivId) {
            return 'arxiv_'.$doc->externalIds->arxivId.'.pdf';
        }

        // Improved fallback: Hash of title + year to reduce collisions
        $hashInput = $doc->title.($doc->year ?? '');

        return 'doc_'.substr(md5($hashInput), 0, 12).'.pdf';
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
                $this->logger->debug('Error getting URL from {source}: {message}', [
                    'source' => $source->getName(),
                    'message' => $e->getMessage(),
                ]);

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

    public function addSource(PDFSourceInterface $source): void
    {
        $this->sources[] = $source;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
