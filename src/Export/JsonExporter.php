<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Utils\Exceptions\ExportError;

class JsonExporter extends JsonlExporter
{
    public function getFileExtension(): string
    {
        return 'json';
    }

    public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $includeRaw = $kwargs['include_raw'] ?? false;
        $indent = $kwargs['indent'] ?? 2;

        try {
            $content = "[\n";
            $items = [];

            foreach ($documents as $doc) {
                $jsonObj = $this->documentToDict($doc, $includeRaw);
                $jsonStr = json_encode($jsonObj, JSON_UNESCAPED_UNICODE);
                $items[] = $jsonStr;
            }

            $content .= implode(",\n", array_map(
                fn ($item, $idx) => ($idx > 0 ? "\n" : '').$this->indentLines($item, $indent),
                $items,
                array_keys($items)
            ));

            $content .= "\n]";

            $this->writeToFile($path, $content);
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write JSON file: {$e->getMessage()}", 'json');
        }

        return $path;
    }

    public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string
    {
        $filename = $this->ensureExtension($outputFile, $this->getFileExtension());
        $path = $this->getOutputPath($filename);

        $mode = $kwargs['mode'] ?? 'clusters';
        $includeRaw = $kwargs['include_raw'] ?? false;
        $indent = $kwargs['indent'] ?? 2;

        try {
            $content = "[\n";
            $items = [];
            $first = true;

            if ($mode === 'clusters') {
                foreach ($clusters as $cluster) {
                    if (! $first) {
                        $items[] = '';
                    }
                    $data = $this->clusterToDict($cluster, $includeRaw);
                    $items[] = json_encode($data, JSON_UNESCAPED_UNICODE);
                    $first = false;
                }
            } elseif ($mode === 'representatives') {
                foreach ($clusters as $cluster) {
                    if (! $first) {
                        $items[] = '';
                    }
                    $data = $this->documentToDict($cluster->representative, $includeRaw);
                    $data['cluster_metadata'] = $this->clusterMetadataToDict($cluster);
                    $items[] = json_encode($data, JSON_UNESCAPED_UNICODE);
                    $first = false;
                }
            } elseif ($mode === 'all') {
                foreach ($clusters as $cluster) {
                    foreach ($cluster->members as $doc) {
                        if (! $first) {
                            $items[] = '';
                        }
                        $data = $this->documentToDict($doc, $includeRaw);
                        $items[] = json_encode($data, JSON_UNESCAPED_UNICODE);
                        $first = false;
                    }
                }
            }

            $content .= implode(",\n", array_map(
                fn ($item, $idx) => ($idx > 0 ? "\n" : '').$this->indentLines($item, $indent),
                $items,
                array_keys($items)
            ));

            $content .= "\n]";

            $this->writeToFile($path, $content);
        } catch (\Throwable $e) {
            throw new ExportError("Failed to write JSON file: {$e->getMessage()}", 'json');
        }

        return $path;
    }

    private function indentLines(string $text, int $spaces): string
    {
        $prefix = str_repeat(' ', $spaces);

        return implode("\n", array_map(
            fn ($line) => $prefix.$line,
            explode("\n", $text)
        ));
    }
}
