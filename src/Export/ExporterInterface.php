<?php

declare(strict_types=1);

namespace Nexus\Export;

use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;

interface ExporterInterface
{
    public function exportDocuments(array $documents, string $outputFile, ...$kwargs): string;

    public function exportClusters(array $clusters, string $outputFile, ...$kwargs): string;

    public function getFileExtension(): string;
}
