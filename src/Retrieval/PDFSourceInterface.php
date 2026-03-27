<?php

namespace Nexus\Retrieval;

use Nexus\Models\Document;

interface PDFSourceInterface
{
    public function getName(): string;

    public function fetch(Document $doc, string $outputPath): bool;
}
