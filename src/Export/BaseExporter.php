<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Utils\Exceptions\ExportError;

abstract class BaseExporter implements ExporterInterface
{
    protected string $outputDir;

    public function __construct(?string $outputDir = null)
    {
        $this->outputDir = $outputDir ?? '.';
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    abstract public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string;

    abstract public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string;

    abstract public function getFileExtension(): string;

    protected function getOutputPath(string $filename): string
    {
        return rtrim($this->outputDir, '/\\').DIRECTORY_SEPARATOR.$filename;
    }

    protected function ensureExtension(string $filename, string $extension): string
    {
        $ext = '.'.ltrim($extension, '.');
        if (! str_ends_with($filename, $ext)) {
            $filename .= $ext;
        }

        return $filename;
    }

    protected function writeToFile(string $path, string $content): void
    {
        $result = file_put_contents($path, $content);
        if ($result === false) {
            throw new ExportError("Failed to write file: {$path}", $this->getFileExtension());
        }
    }
}
