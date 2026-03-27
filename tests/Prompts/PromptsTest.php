<?php

namespace Nexus\Tests\Prompts;

use Nexus\Prompts\MegaPrompts;
use Nexus\Prompts\PromptHelpers;
use Nexus\Prompts\SystemPrompts;
use PHPUnit\Framework\TestCase;

class PromptsTest extends TestCase
{
    public function test_system_prompts_exist(): void
    {
        $this->assertNotEmpty(SystemPrompts::SYSTEMATIC_REVIEW_EXPERT);
        $this->assertNotEmpty(SystemPrompts::RESEARCH_ASSISTANT);
        $this->assertNotEmpty(SystemPrompts::META_ANALYSIS_ASSISTANT);
        $this->assertNotEmpty(SystemPrompts::LITERATURE_MAPPING_ASSISTANT);
    }

    public function test_systematic_review_expert_contains_key_concepts(): void
    {
        $prompt = SystemPrompts::SYSTEMATIC_REVIEW_EXPERT;
        
        $this->assertStringContainsString('systematic literature review', $prompt);
        $this->assertStringContainsString('PRISMA', $prompt);
        $this->assertStringContainsString('Boolean', $prompt);
        $this->assertStringContainsString('inclusion/exclusion', $prompt);
    }

    public function test_research_assistant_contains_capabilities(): void
    {
        $prompt = SystemPrompts::RESEARCH_ASSISTANT;
        
        $this->assertStringContainsString('Literature Discovery', $prompt);
        $this->assertStringContainsString('Search Optimization', $prompt);
        $this->assertStringContainsString('Information Extraction', $prompt);
    }

    public function test_meta_analysis_assistant_contains_statistical_terms(): void
    {
        $prompt = SystemPrompts::META_ANALYSIS_ASSISTANT;
        
        $this->assertStringContainsString('effect size', strtolower($prompt));
        $this->assertStringContainsString('heterogeneity', strtolower($prompt));
        $this->assertStringContainsString('publication bias', strtolower($prompt));
    }

    public function test_literature_mapping_assistant_contains_bibliometric_terms(): void
    {
        $prompt = SystemPrompts::LITERATURE_MAPPING_ASSISTANT;
        
        $this->assertStringContainsString('Bibliometric', $prompt);
        $this->assertStringContainsString('co-citation', strtolower($prompt));
        $this->assertStringContainsString('thematic', strtolower($prompt));
    }

    public function test_mega_prompts_exist(): void
    {
        $this->assertNotEmpty(MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW);
        $this->assertNotEmpty(MegaPrompts::RESEARCH_BASELINE);
        $this->assertNotEmpty(MegaPrompts::COMPARATIVE_ANALYSIS);
        $this->assertNotEmpty(MegaPrompts::GAP_ANALYSIS);
        $this->assertNotEmpty(MegaPrompts::META_ANALYSIS_PROTOCOL);
        $this->assertNotEmpty(MegaPrompts::EXPERT_INTERVIEW_PREPARATION);
        $this->assertNotEmpty(MegaPrompts::RESEARCH_PROPOSAL_FOUNDATION);
        $this->assertNotEmpty(MegaPrompts::ANNOTATED_BIBLIOGRAPHY);
    }

    public function test_comprehensive_review_has_phases(): void
    {
        $prompt = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
        
        $this->assertStringContainsString('PHASE 1', $prompt);
        $this->assertStringContainsString('PHASE 2', $prompt);
        $this->assertStringContainsString('PHASE 3', $prompt);
        $this->assertStringContainsString('PHASE 4', $prompt);
        $this->assertStringContainsString('PHASE 5', $prompt);
    }

    public function test_comprehensive_review_has_query_placeholder(): void
    {
        $prompt = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
        
        $this->assertStringContainsString('{query}', $prompt);
    }

    public function test_gap_analysis_has_steps(): void
    {
        $prompt = MegaPrompts::GAP_ANALYSIS;
        
        $this->assertStringContainsString('Step 1', $prompt);
        $this->assertStringContainsString('Step 2', $prompt);
        $this->assertStringContainsString('Step 3', $prompt);
        $this->assertStringContainsString('Step 4', $prompt);
        $this->assertStringContainsString('Step 5', $prompt);
    }

    public function test_meta_analysis_protocol_has_components(): void
    {
        $prompt = MegaPrompts::META_ANALYSIS_PROTOCOL;
        
        $this->assertStringContainsString('BACKGROUND AND RATIONALE', $prompt);
        $this->assertStringContainsString('REVIEW QUESTIONS', $prompt);
        $this->assertStringContainsString('INCLUSION CRITERIA', $prompt);
        $this->assertStringContainsString('SEARCH STRATEGY', $prompt);
        $this->assertStringContainsString('DATA SYNTHESIS', $prompt);
    }

    public function test_annotated_bibliography_has_requirements(): void
    {
        $prompt = MegaPrompts::ANNOTATED_BIBLIOGRAPHY;
        
        $this->assertStringContainsString('Bibliographic Information', $prompt);
        $this->assertStringContainsString('Study Characteristics', $prompt);
        $this->assertStringContainsString('Key Findings', $prompt);
        $this->assertStringContainsString('Critical Analysis', $prompt);
        $this->assertStringContainsString('Relevance', $prompt);
    }

    public function test_year_filters_exist(): void
    {
        $filters = PromptHelpers::YEAR_FILTERS;
        
        $this->assertArrayHasKey('recent', $filters);
        $this->assertArrayHasKey('last_decade', $filters);
        $this->assertArrayHasKey('last_two_decades', $filters);
        $this->assertArrayHasKey('all_time', $filters);
        $this->assertArrayHasKey('foundational', $filters);
    }

    public function test_citation_filters_exist(): void
    {
        $filters = PromptHelpers::CITATION_FILTERS;
        
        $this->assertArrayHasKey('highly_cited', $filters);
        $this->assertArrayHasKey('influential', $filters);
        $this->assertArrayHasKey('any', $filters);
    }

    public function test_study_types_exist(): void
    {
        $types = PromptHelpers::STUDY_TYPES;
        
        $this->assertArrayHasKey('empirical', $types);
        $this->assertArrayHasKey('systematic_review', $types);
        $this->assertArrayHasKey('theoretical', $types);
        $this->assertArrayHasKey('methodological', $types);
        $this->assertArrayHasKey('review', $types);
    }

    public function test_open_access_preferences_exist(): void
    {
        $prefs = PromptHelpers::OPEN_ACCESS_PREFERENCES;
        
        $this->assertArrayHasKey('oa_only', $prefs);
        $this->assertArrayHasKey('prefer_oa', $prefs);
        $this->assertArrayHasKey('any', $prefs);
    }

    public function test_build_search_query_returns_expected_structure(): void
    {
        $result = PromptHelpers::buildSearchQuery(
            mainConcept: 'machine learning',
            keywords: ['healthcare', 'diagnosis'],
            yearFilter: 'last_decade',
            studyType: 'empirical',
            oaPreference: 'prefer_oa'
        );

        $this->assertArrayHasKey('primary_query', $result);
        $this->assertArrayHasKey('boolean_variations', $result);
        $this->assertArrayHasKey('year_filter', $result);
        $this->assertArrayHasKey('study_type_filter', $result);
        $this->assertArrayHasKey('open_access', $result);
        
        $this->assertEquals('machine learning', $result['primary_query']);
        $this->assertCount(2, $result['boolean_variations']);
        $this->assertEquals('Last 10 years', $result['year_filter']);
        $this->assertEquals('empirical', $result['study_type_filter']);
    }

    public function test_build_search_query_with_defaults(): void
    {
        $result = PromptHelpers::buildSearchQuery(
            mainConcept: 'test'
        );

        $this->assertEquals('test', $result['primary_query']);
        $this->assertEmpty($result['boolean_variations']);
        $this->assertEquals('Last 10 years', $result['year_filter']);
        $this->assertNull($result['study_type_filter']);
        $this->assertEquals('Prefer open access', $result['open_access']);
    }

    public function test_format_for_prisma_returns_expected_structure(): void
    {
        $result = PromptHelpers::formatForPRISMA(
            initial: 5000,
            duplicates: 1000,
            titleAbstract: 3000,
            fullText: 500,
            included: 150
        );

        $this->assertArrayHasKey('records_identified', $result);
        $this->assertArrayHasKey('records_screened', $result);
        $this->assertArrayHasKey('duplicates_removed', $result);
        $this->assertArrayHasKey('full_text_assessed', $result);
        $this->assertArrayHasKey('qualitative_synthesis', $result);
        $this->assertArrayHasKey('quantitative_synthesis', $result);
        
        $this->assertEquals(5000, $result['records_identified']);
        $this->assertEquals(4000, $result['records_screened']);
        $this->assertEquals(1000, $result['duplicates_removed']);
        $this->assertEquals(3000, $result['full_text_assessed']);
        $this->assertEquals(500, $result['qualitative_synthesis']);
        $this->assertEquals(150, $result['quantitative_synthesis']);
    }

    public function test_research_baseline_has_components(): void
    {
        $prompt = MegaPrompts::RESEARCH_BASELINE;
        
        $this->assertStringContainsString('COMPONENT 1', $prompt);
        $this->assertStringContainsStringIgnoringCase('Foundational', $prompt);
        $this->assertStringContainsString('COMPONENT 2', $prompt);
        $this->assertStringContainsStringIgnoringCase('Theoretical', $prompt);
        $this->assertStringContainsString('COMPONENT 3', $prompt);
        $this->assertStringContainsStringIgnoringCase('Methodological', $prompt);
    }

    public function test_comparative_analysis_has_dimensions(): void
    {
        $prompt = MegaPrompts::COMPARATIVE_ANALYSIS;
        
        $this->assertStringContainsString('Dimension 1', $prompt);
        $this->assertStringContainsString('Theoretical Foundations', $prompt);
        $this->assertStringContainsString('Dimension 2', $prompt);
        $this->assertStringContainsString('Methodological Approaches', $prompt);
    }

    public function test_expert_interview_has_domains(): void
    {
        $prompt = MegaPrompts::EXPERT_INTERVIEW_PREPARATION;
        
        $this->assertStringContainsString('Domain 1', $prompt);
        $this->assertStringContainsString('Theoretical Perspectives', $prompt);
        $this->assertStringContainsString('Domain 2', $prompt);
        $this->assertStringContainsString('Empirical Evidence', $prompt);
    }

    public function test_research_proposal_has_components(): void
    {
        $prompt = MegaPrompts::RESEARCH_PROPOSAL_FOUNDATION;
        
        $this->assertStringContainsString('PROBLEM STATEMENT', $prompt);
        $this->assertStringContainsString('SIGNIFICANCE', $prompt);
        $this->assertStringContainsString('THEORETICAL FRAMEWORK', $prompt);
        $this->assertStringContainsString('LITERATURE REVIEW', $prompt);
    }
}
