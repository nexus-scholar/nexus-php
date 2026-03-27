<?php

namespace Nexus\Dedup;

use Nexus\Models\Document;
use Nexus\Models\DocumentCluster;
use Nexus\Utils\UnionFind;

class ConservativeStrategy extends DeduplicationStrategy
{
    /**
     * @param Document[] $documents
     * @return DocumentCluster[]
     */
    public function deduplicate(array $documents, ?callable $progressCallback = null): array
    {
        if (empty($documents)) {
            return [];
        }

        $n = count($documents);
        $uf = new UnionFind($n);

        $doiIndex = [];
        $arxivIndex = [];
        $titleIndex = [];
        
        $normTitles = [];
        $titleWordSets = [];

        if ($progressCallback) {
            $progressCallback("Preprocessing titles...", 5);
        }

        foreach ($documents as $idx => $doc) {
            if ($doc->externalIds->doi) {
                $doi = self::normalizeDoi($doc->externalIds->doi);
                if ($doi) {
                    $doiIndex[$doi][] = $idx;
                }
            }
            if ($doc->externalIds->arxivId) {
                $arxivId = strtolower(trim($doc->externalIds->arxivId));
                if ($arxivId) {
                    $arxivIndex[$arxivId][] = $idx;
                }
            }
            
            $nt = self::normalizeTitle($doc->title);
            $normTitles[$idx] = $nt;
            $titleWordSets[$idx] = $nt ? array_flip(explode(' ', $nt)) : [];
            
            if ($nt) {
                $titleIndex[$nt][] = $idx;
            }
        }

        // Phase 1: Exact matches
        if ($progressCallback) {
            $progressCallback("Matching exact identifiers...", 10);
        }
        foreach ($doiIndex as $indices) {
            for ($i = 1; $i < count($indices); $i++) {
                $uf->union($indices[0], $indices[$i]);
            }
        }
        foreach ($arxivIndex as $indices) {
            for ($i = 1; $i < count($indices); $i++) {
                $uf->union($indices[0], $indices[$i]);
            }
        }

        // Phase 2: Exact Title Blocking
        if ($progressCallback) {
            $progressCallback("Matching exact titles...", 15);
        }
        foreach ($titleIndex as $indices) {
            for ($i = 1; $i < count($indices); $i++) {
                $uf->union($indices[0], $indices[$i]);
            }
        }

        // Phase 3: Fuzzy matching
        $docsByYear = [];
        foreach ($documents as $idx => $doc) {
            if ($doc->year) {
                $docsByYear[$doc->year][] = $idx;
            }
        }

        $years = array_keys($docsByYear);
        sort($years);
        $totalYears = count($years);

        foreach ($years as $i => $year) {
            if ($progressCallback) {
                $percent = 20 + (int)(70 * ($i / max(1, $totalYears)));
                $progressCallback("Fuzzy matching year $year...", $percent);
            }

            // Get candidate indices from years within the allowed gap
            $candidates = [];
            foreach ($years as $j => $otherYear) {
                if ($j < $i) continue;
                if ($otherYear - $year > $this->config->maxYearGap) break;
                foreach ($docsByYear[$otherYear] as $idx) {
                    $candidates[] = $idx;
                }
            }
            
            foreach ($docsByYear[$year] as $idxA) {
                $wordsA = $titleWordSets[$idxA];
                if (empty($wordsA)) continue;
                
                foreach ($candidates as $idxB) {
                    if ($idxA >= $idxB) continue;
                    if ($uf->find($idxA) === $uf->find($idxB)) continue;
                    
                    $wordsB = $titleWordSets[$idxB];
                    if (empty($wordsB)) continue;
                    
                    // Set-intersection pruning (fast check)
                    $common = count(array_intersect_key($wordsA, $wordsB));
                    if ($common < 2) continue;
                    
                    // Fuzzy ratio calculation using levenshtein
                    $score = $this->calculateFuzzyRatio($normTitles[$idxA], $normTitles[$idxB]);
                    if ($score >= $this->config->fuzzyThreshold) {
                        $uf->union($idxA, $idxB);
                    }
                }
            }
        }

        // Finalize clusters
        if ($progressCallback) {
            $progressCallback("Generating final clusters...", 95);
        }
        
        $clustersMap = [];
        for ($idx = 0; $idx < $n; $idx++) {
            $root = $uf->find($idx);
            $clustersMap[$root][] = $documents[$idx];
        }

        $results = [];
        $clusterIdCounter = 0;
        foreach ($clustersMap as $clusterDocs) {
            $results[] = self::createCluster($clusterIdCounter++, $clusterDocs);
        }

        return $results;
    }

    private function calculateFuzzyRatio(string $s1, string $s2): int
    {
        if ($s1 === $s2) return 100;
        $len1 = strlen($s1);
        $len2 = strlen($s2);
        if ($len1 === 0 || $len2 === 0) return 0;

        $dist = levenshtein($s1, $s2);
        $maxLen = max($len1, $len2);
        
        return (int)((1 - $dist / $maxLen) * 100);
    }
}
