<?php

namespace Nexus\Core;

use Generator;
use Nexus\Dedup\ConservativeStrategy;
use Nexus\Dedup\DeduplicationStrategy;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Models\SnowballConfig;

class SnowballService
{
    private DeduplicationStrategy $dedupStrategy;
    /** @var SnowballProviderInterface[] */
    private array $providers;

    public function __construct(
        private SnowballConfig $config,
        SnowballProviderInterface ...$providers
    ) {
        $dedupConfig = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::CONSERVATIVE,
            fuzzyThreshold: 97,
            maxYearGap: 1
        );
        $this->dedupStrategy = new ConservativeStrategy($dedupConfig);
        $this->providers = $providers;
    }

    public function setDeduplicationStrategy(DeduplicationStrategy $strategy): void
    {
        $this->dedupStrategy = $strategy;
    }

    public function snowball(Document $seed, array $existingDocuments): array
    {
        $newDocuments = [];
        $allExisting = $existingDocuments;

        foreach ($this->providers as $provider) {
            if ($this->config->forward) {
                foreach ($provider->getCitingDocuments($seed, $this->config->maxCitations) as $doc) {
                    $newDocuments[] = $doc;
                }
            }

            if ($this->config->backward) {
                foreach ($provider->getReferencedDocuments($seed, $this->config->maxReferences) as $doc) {
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
                $normTitle = DeduplicationStrategy::normalizeTitle($doc->title);
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
        if ($doc->title && isset($idSet['title'][DeduplicationStrategy::normalizeTitle($doc->title)])) {
            return true;
        }

        return false;
    }
}
