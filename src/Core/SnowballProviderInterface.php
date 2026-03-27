<?php

namespace Nexus\Core;

use Generator;
use Nexus\Models\Document;

interface SnowballProviderInterface
{
    /**
     * Get documents that cite the given document.
     *
     * @param Document $document
     * @param int $limit
     * @return Generator<Document>
     */
    public function getCitingDocuments(Document $document, int $limit = 100): Generator;

    /**
     * Get documents referenced by the given document.
     *
     * @param Document $document
     * @param int $limit
     * @return Generator<Document>
     */
    public function getReferencedDocuments(Document $document, int $limit = 50): Generator;
}
