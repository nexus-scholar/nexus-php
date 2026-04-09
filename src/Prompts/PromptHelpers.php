<?php

namespace Nexus\Prompts;

class PromptHelpers
{
    public const YEAR_FILTERS = [
        'recent' => 'Last 5 years',
        'last_decade' => 'Last 10 years',
        'last_two_decades' => 'Last 20 years',
        'all_time' => 'No date restriction',
        'foundational' => 'First publications in field',
    ];

    public const CITATION_FILTERS = [
        'highly_cited' => '100+ citations',
        'influential' => '50+ citations',
        'recent_high_impact' => 'Recent papers with 20+ citations',
        'any' => 'No citation filter',
    ];

    public const STUDY_TYPES = [
        'empirical' => 'Empirical studies (quantitative, qualitative, mixed)',
        'systematic_review' => 'Systematic reviews and meta-analyses',
        'theoretical' => 'Theoretical and conceptual papers',
        'methodological' => 'Methodological papers',
        'review' => 'Narrative reviews and editorials',
        'protocol' => 'Study protocols',
        'preprint' => 'Preprints (marked as non-peer-reviewed)',
    ];

    public const OPEN_ACCESS_PREFERENCES = [
        'oa_only' => 'Open access only',
        'prefer_oa' => 'Prefer open access',
        'any' => 'No preference (may include paywalled)',
    ];

    public static function buildSearchQuery(
        string $mainConcept,
        array $keywords = [],
        string $yearFilter = 'last_decade',
        ?string $studyType = null,
        string $oaPreference = 'prefer_oa'
    ): array {
        $query = $mainConcept;

        $booleanModifiers = [];
        foreach ($keywords as $keyword) {
            $booleanModifiers[] = "({$mainConcept} AND {$keyword})";
        }

        return [
            'primary_query' => $query,
            'boolean_variations' => $booleanModifiers,
            'year_filter' => self::YEAR_FILTERS[$yearFilter] ?? 'Last 10 years',
            'study_type_filter' => $studyType,
            'open_access' => self::OPEN_ACCESS_PREFERENCES[$oaPreference] ?? 'Prefer open access',
        ];
    }

    public static function formatForPRISMA(int $initial, int $duplicates, int $titleAbstract, int $fullText, int $included): array
    {
        return [
            'records_identified' => $initial,
            'records_screened' => $initial - $duplicates,
            'duplicates_removed' => $duplicates,
            'full_text_assessed' => $titleAbstract,
            'full_text_excluded' => $titleAbstract - $fullText,
            'qualitative_synthesis' => $fullText,
            'quantitative_synthesis' => $included,
            'reasons_for_exclusion' => [],
        ];
    }
}
