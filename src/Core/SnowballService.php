<?php

namespace Nexus\Core;

use Generator;
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Models\SnowballConfig;
use Nexus\Providers\OpenAlexProvider;
use Nexus\Providers\SemanticScholarProvider;

class SnowballService
{
    private ConservativeStrategy $dedupStrategy;

    public function __construct(
        private SnowballConfig $config,
        private OpenAlexProvider $openalex,
        private SemanticScholarProvider $semanticScholar
    ) {
        $dedupConfig = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::CONSERVATIVE,
            fuzzyThreshold: 97,
            maxYearGap: 1
        );
        $this->dedupStrategy = new ConservativeStrategy($dedupConfig);
    }

    public function snowball(Document $seed, array $existingDocuments): array
    {
        $newDocuments = [];
        $allExisting = $existingDocuments;

        $openalexId = $seed->externalIds->openalexId;
        $s2Id = $seed->externalIds->s2Id;

        if ($this->config->forward) {
            if ($openalexId) {
                foreach ($this->openalex->getCitingWorks($openalexId, $this->config->maxCitations) as $doc) {
                    $newDocuments[] = $doc;
                }
            }

            if ($s2Id) {
                foreach ($this->semanticScholar->getCitingPapers($s2Id, $this->config->maxCitations) as $doc) {
                    $newDocuments[] = $doc;
                }
            }
        }

        if ($this->config->backward) {
            if ($openalexId) {
                foreach ($this->openalex->getReferencedWorks($openalexId, $this->config->maxReferences) as $doc) {
                    $newDocuments[] = $doc;
                }
            }

            if ($s2Id) {
                foreach ($this->semanticScholar->getReferences($s2Id, $this->config->maxReferences) as $doc) {
                    $newDocuments[] = $doc;
                }
            }
        }

        if (empty($newDocuments)) {
            return [];
        }

        $clusters = $this->dedupStrategy->deduplicate($newDocuments);
        
        $uniqueDocuments = $this->filterAgainstExisting($clusters, $allExisting);

        return $uniqueDocuments;
    }

    public function snowballMultiple(array $seeds, array $existingDocuments, int $currentDepth = 0): array
    {
        if ($currentDepth >= $this->config->depth) {
            return [];
        }

        $allNewDocuments = [];
        $allExisting = $existingDocuments;

        foreach ($seeds as $seed) {
            $newDocs = $this->snowball($seed, $allExisting);
            foreach ($newDocs as $doc) {
                $allExisting[] = $doc;
            }
            $allNewDocuments = array_merge($allNewDocuments, $newDocs);
        }

        if ($currentDepth + 1 < $this->config->depth && !empty($allNewDocuments)) {
            $recursiveDocs = $this->snowballMultiple($allNewDocuments, $allExisting, $currentDepth + 1);
            $allNewDocuments = array_merge($allNewDocuments, $recursiveDocs);
        }

        return $allNewDocuments;
    }

    private function filterAgainstExisting(array $clusters, array $existingDocuments): array
    {
        $existingIds = $this->buildIdSet($existingDocuments);
        
        $uniqueDocuments = [];
        
        foreach ($clusters as $cluster) {
            $doc = $cluster->representative;
            
            if (!$this->isDocumentInSet($doc, $existingIds)) {
                $uniqueDocuments[] = $doc;
            }
        }

        return $uniqueDocuments;
    }

    private function buildIdSet(array $documents): array
    {
        $ids = [
            'doi' => [],
            'arxiv' => [],
            'openalex' => [],
            's2' => [],
            'title' => [],
        ];

        foreach ($documents as $doc) {
            if ($doc->externalIds->doi) {
                $ids['doi'][strtolower($doc->externalIds->doi)] = true;
            }
            if ($doc->externalIds->arxivId) {
                $ids['arxiv'][strtolower($doc->externalIds->arxivId)] = true;
            }
            if ($doc->externalIds->openalexId) {
                $ids['openalex'][strtolower($doc->externalIds->openalexId)] = true;
            }
            if ($doc->externalIds->s2Id) {
                $ids['s2'][strtolower($doc->externalIds->s2Id)] = true;
            }
            if ($doc->title) {
                $normTitle = ConservativeStrategy::normalizeTitle($doc->title);
                $ids['title'][$normTitle] = true;
            }
        }

        return $ids;
    }

    private function isDocumentInSet(Document $doc, array $idSet): bool
    {
        if ($doc->externalIds->doi && isset($idSet['doi'][strtolower($doc->externalIds->doi)])) {
            return true;
        }
        if ($doc->externalIds->arxivId && isset($idSet['arxiv'][strtolower($doc->externalIds->arxivId)])) {
            return true;
        }
        if ($doc->externalIds->openalexId && isset($idSet['openalex'][strtolower($doc->externalIds->openalexId)])) {
            return true;
        }
        if ($doc->externalIds->s2Id && isset($idSet['s2'][strtolower($doc->externalIds->s2Id)])) {
            return true;
        }
        if ($doc->title && isset($idSet['title'][ConservativeStrategy::normalizeTitle($doc->title)])) {
            return true;
        }

        return false;
    }
}
