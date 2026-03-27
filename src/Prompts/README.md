# Nexus Prompts Library

A comprehensive collection of system prompts and mega prompts for AI-assisted systematic literature reviews and academic research.

## Overview

The Prompts library provides pre-crafted prompts for various research workflows, from basic literature discovery to comprehensive systematic reviews. These prompts are designed to work with AI agents equipped with the Nexus LiteratureSearchTool.

## Quick Start

```php
use Nexus\Prompts\SystemPrompts;
use Nexus\Prompts\MegaPrompts;
use Nexus\Prompts\PromptHelpers;

// Use a system prompt for an AI agent
$agentInstructions = SystemPrompts::RESEARCH_ASSISTANT;

// Use a mega prompt for a comprehensive task
$taskPrompt = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
$taskPrompt = str_replace('{query}', 'machine learning in healthcare', $taskPrompt);
```

## Prompt Categories

### 1. System Prompts (`SystemPrompts`)

Role-based prompts that define AI agent behavior and capabilities.

#### SystemPrompts::SYSTEMATIC_REVIEW_EXPERT

**Use case**: When conducting formal systematic literature reviews following PRISMA guidelines.

**Persona**: A systematic review expert with deep knowledge of:

- Academic database operations
- Boolean query construction
- PRISMA compliance
- Duplicate detection
- Citation screening

**Best for**:

- Systematic reviews
- Meta-analyses
- Cochrane-style reviews
- Protocol development

#### SystemPrompts::RESEARCH_ASSISTANT

**Use case**: General-purpose literature research and discovery.

**Persona**: An AI research assistant specializing in:

- Academic literature discovery
- Search optimization
- Information extraction
- Citation tracking

**Best for**:

- Initial literature exploration
- Finding related work
- Research preparation
- Citation verification

#### SystemPrompts::META_ANALYSIS_ASSISTANT

**Use case**: Quantitative synthesis of research findings.

**Persona**: A meta-analysis specialist with expertise in:

- Effect size calculation
- Heterogeneity assessment
- Publication bias detection
- PRISMA-MA guidelines

**Best for**:

- Meta-analysis planning
- Effect size extraction
- Quality assessment
- Statistical synthesis

#### SystemPrompts::LITERATURE_MAPPING_ASSISTANT

**Use case**: Bibliometric analysis and knowledge mapping.

**Persona**: A research mapping specialist skilled in:

- Bibliometric analysis
- Co-citation networks
- Thematic clustering
- Visualization preparation

**Best for**:

- Systematic mapping reviews
- Bibliometric studies
- Research landscape analysis
- Emerging field identification

---

### 2. Mega Prompts (`MegaPrompts`)

Comprehensive, multi-phase prompts for complex research workflows.

#### MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW

**Purpose**: Conduct a full systematic literature review from search to synthesis.

**Phases**:

1. Search strategy development
2. Search execution
3. Screening and selection
4. Data extraction
5. Synthesis

**Output**:

- Search strategy documentation
- PRISMA flow diagram data
- Evidence tables
- Thematic synthesis
- Gap identification
- Future research recommendations

**Usage**:

```php
$prompt = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
$prompt = str_replace('{query}', 'transformer models for NLP', $prompt);
```

#### MegaPrompts::RESEARCH_BASELINE

**Purpose**: Establish foundational knowledge for a research project.

**Components**:

1. Foundational works identification
2. Theoretical framework mapping
3. Methodological landscape
4. Empirical findings synthesis
5. Knowledge gap identification
6. Research frontier documentation

**Output**:

- Research handbook
- Annotated bibliography
- Visual concept maps (textual)
- Prioritized reading list

**Usage**:

```php
$prompt = MegaPrompts::RESEARCH_BASELINE;
$prompt = str_replace('{query}', 'federated learning', $prompt);
```

#### MegaPrompts::COMPARATIVE_ANALYSIS

**Purpose**: Compare and contrast different approaches, methods, or perspectives.

**Dimensions**:

1. Theoretical foundations
2. Methodological approaches
3. Scope and application
4. Evidence base
5. Practical implications

**Output**:

- Comparative matrix table
- Narrative analysis
- Synthesis and recommendations
- Future directions

**Usage**:

```php
$prompt = MegaPrompts::COMPARATIVE_ANALYSIS;
$prompt = str_replace('{query}', 'CNN vs ViT for image classification', $prompt);
```

#### MegaPrompts::GAP_ANALYSIS

**Purpose**: Systematically identify research gaps.

**Steps**:

1. Scope definition
2. Knowledge mapping
3. Gap identification (theoretical, methodological, empirical, practical)
4. Gap prioritization
5. Research agenda development

**Output**:

- Gap taxonomy
- Gap matrix (importance vs. feasibility)
- Research questions for each gap
- Phased research agenda

**Usage**:

```php
$prompt = MegaPrompts::GAP_ANALYSIS;
$prompt = str_replace('{query}', 'AI ethics in healthcare', $prompt);
```

#### MegaPrompts::META_ANALYSIS_PROTOCOL

**Purpose**: Design a complete meta-analysis protocol.

**Sections**:

1. Background and rationale
2. Review questions
3. Inclusion criteria
4. Search strategy
5. Study selection
6. Data extraction
7. Data synthesis plan
8. Analysis plan
9. Reporting guidelines
10. Timeline

**Output**:

- PRISMA-MA compliant protocol
- Statistical analysis plan
- Quality assessment framework
- Timeline estimation

**Usage**:

```php
$prompt = MegaPrompts::META_ANALYSIS_PROTOCOL;
$prompt = str_replace('{query}', 'effectiveness of mindfulness on anxiety', $prompt);
```

#### MegaPrompts::EXPERT_INTERVIEW_PREPARATION

**Purpose**: Prepare for expert interviews based on literature review.

**Components**:

1. Literature synthesis
2. Interview domains (theoretical, empirical, methodological, practical, future)
3. Question types (factual, opinion, experience, future)
4. Interview structure
5. Documentation templates

**Output**:

- Structured interview guide
- Domain-specific questions
- Opening/closing scripts
- Follow-up templates

**Usage**:

```php
$prompt = MegaPrompts::EXPERT_INTERVIEW_PREPARATION;
$prompt = str_replace('{query}', 'software architecture patterns', $prompt);
```

#### MegaPrompts::RESEARCH_PROPOSAL_FOUNDATION

**Purpose**: Build literature foundation for a research proposal.

**Sections**:

1. Problem statement
2. Significance
3. Theoretical framework
4. Literature review
5. Knowledge gaps
6. Research questions/hypotheses
7. Methodology selection
8. Expected contributions

**Output**:

- Complete literature review structure
- Citation tracking system
- Gap-driven research questions
- Proposal narrative support

**Usage**:

```php
$prompt = MegaPrompts::RESEARCH_PROPOSAL_FOUNDATION;
$prompt = str_replace('{query}', 'quantum machine learning', $prompt);
```

#### MegaPrompts::ANNOTATED_BIBLIOGRAPHY

**Purpose**: Create a comprehensive annotated bibliography.

**Requirements per source**:

- Bibliographic information
- Study characteristics
- Key findings
- Critical analysis
- Relevance assessment
- Quotable passages

**Organization**:

- By theme/topic
- Within themes: foundational → recent → methodological → reviews

**Output**:

- Structured annotations
- Quality indicators
- Synthesis opportunities

**Usage**:

```php
$prompt = MegaPrompts::ANNOTATED_BIBLIOGRAPHY;
$prompt = str_replace('{query}', 'reinforcement learning applications', $prompt);
```

---

### 3. Prompt Helpers (`PromptHelpers`)

Utility functions for building and formatting prompts.

#### Filter Constants

**YEAR_FILTERS**:

```php
PromptHelpers::YEAR_FILTERS['recent']       // Last 5 years
PromptHelpers::YEAR_FILTERS['last_decade'] // Last 10 years
PromptHelpers::YEAR_FILTERS['last_two_decades'] // Last 20 years
PromptHelpers::YEAR_FILTERS['all_time']    // No restriction
PromptHelpers::YEAR_FILTERS['foundational'] // First publications
```

**CITATION_FILTERS**:

```php
PromptHelpers::CITATION_FILTERS['highly_cited']      // 100+ citations
PromptHelpers::CITATION_FILTERS['influential']       // 50+ citations
PromptHelpers::CITATION_FILTERS['recent_high_impact'] // Recent with 20+ citations
PromptHelpers::CITATION_FILTERS['any']              // No filter
```

**STUDY_TYPES**:

```php
PromptHelpers::STUDY_TYPES['empirical']        // Empirical studies
PromptHelpers::STUDY_TYPES['systematic_review'] // Reviews & meta-analyses
PromptHelpers::STUDY_TYPES['theoretical']      // Theoretical papers
PromptHelpers::STUDY_TYPES['methodological']    // Methods papers
PromptHelpers::STUDY_TYPES['review']            // Narrative reviews
PromptHelpers::STUDY_TYPES['protocol']         // Study protocols
PromptHelpers::STUDY_TYPES['preprint']          // Preprints
```

**OPEN_ACCESS_PREFERENCES**:

```php
PromptHelpers::OPEN_ACCESS_PREFERENCES['oa_only']    // Open access only
PromptHelpers::OPEN_ACCESS_PREFERENCES['prefer_oa']  // Prefer OA
PromptHelpers::OPEN_ACCESS_PREFERENCES['any']        // No preference
```

#### Helper Functions

**buildSearchQuery()**:

```php
$query = PromptHelpers::buildSearchQuery(
    mainConcept: 'machine learning',
    keywords: ['healthcare', 'diagnosis', 'prediction'],
    yearFilter: 'last_decade',
    studyType: 'empirical',
    oaPreference: 'prefer_oa'
);

// Returns:
// [
//     'primary_query' => 'machine learning',
//     'boolean_variations' => [
//         '(machine learning AND healthcare)',
//         '(machine learning AND diagnosis)',
//         '(machine learning AND prediction)',
//     ],
//     'year_filter' => 'Last 10 years',
//     'study_type_filter' => 'empirical',
//     'open_access' => 'Prefer open access',
// ]
```

**formatForPRISMA()**:

```php
$prisma = PromptHelpers::formatForPRISMA(
    initial: 5000,
    duplicates: 1200,
    titleAbstract: 2500,
    fullText: 400,
    included: 150
);

// Returns tracking data for PRISMA flow diagram
```

---

## Integration with AI Agents

### Using with LiteratureSearchAgent

```php
use Nexus\Laravel\Agents\LiteratureSearchAgent;
use Nexus\Prompts\SystemPrompts;

$agent = LiteratureSearchAgent::make()
    ->withInstructions(SystemPrompts::SYSTEMATIC_REVIEW_EXPERT)
    ->withMaxResults(50)
    ->withProvider('openalex');

// The agent will use the systematic review expert persona
// when processing research queries
```

### Using with Laravel AI SDK

```php
use Nexus\Prompts\SystemPrompts;
use Nexus\Prompts\MegaPrompts;
use Nexus\Laravel\Tools\LiteratureSearchTool;
use Laravel\Ai\AnonymouAgent;

// Create an agent with Nexus tools
$agent = new AnonymousAgent(
    instructions: SystemPrompts::META_ANALYSIS_ASSISTANT,
    messages: [],
    tools: [
        LiteratureSearchTool::make()->withProviders(['openalex', 'crossref']),
    ]
);

// For a specific task
$taskPrompt = MegaPrompts::GAP_ANALYSIS;
$taskPrompt = str_replace('{query}', 'your research topic', $taskPrompt);

$response = $agent->prompt($taskPrompt);
```

---

## Prompt Engineering Tips

### Customization Guidelines

1. **Replace placeholders**: Always replace `{query}` with your specific topic
2. **Adjust scope**: Modify year filters and study types based on your needs
3. **Combine prompts**: Mix system prompts with mega prompts for complex workflows

### Best Practices

1. **Start broad**: Begin with RESEARCH_ASSISTANT for exploration
2. **Iterate**: Refine searches based on initial results
3. **Document**: Use the PRISMA formatting for reproducibility
4. **Validate**: Cross-check AI findings against known literature

### Limitations

- AI may not access paywalled content
- Citation counts may be delayed
- Always verify critical findings manually
- Review papers should be flagged appropriately

---

## Example Workflows

### 1. Rapid Literature Scan (1 hour)

```php
$agent = LiteratureSearchAgent::make()
    ->withInstructions(SystemPrompts::RESEARCH_ASSISTANT)
    ->withMaxResults(30);

$response = $agent->prompt("Find recent papers on {$topic}");
```

### 2. Systematic Review (1-2 weeks)

```php
// Phase 1: Search strategy
$searchPrompt = MegaPrompts::COMPREHENSIVE_LITERATURE_REVIEW;
$searchPrompt = str_replace('{query}', $topic, $searchPrompt);

// Phase 2: Execute searches with multiple providers
$tool = LiteratureSearchTool::make()
    ->withProviders(['openalex', 'crossref', 'pubmed'])
    ->withAbstract(true);

// Phase 3: Analysis with meta-analysis prompt
$analysisPrompt = MegaPrompts::META_ANALYSIS_PROTOCOL;
```

### 3. Research Proposal (2-3 days)

```php
// Foundation building
$founderPrompt = MegaPrompts::RESEARCH_BASELINE;
$founderPrompt = str_replace('{query}', $topic, $founderPrompt);

// Gap analysis
$gapPrompt = MegaPrompts::GAP_ANALYSIS;
$gapPrompt = str_replace('{query}', $topic, $gapPrompt);

// Proposal foundation
$proposalPrompt = MegaPrompts::RESEARCH_PROPOSAL_FOUNDATION;
$proposalPrompt = str_replace('{query}', $topic, $proposalPrompt);
```

---

## API Reference

### SystemPrompts Constants

| Constant                       | Description                       | Best For                  |
| ------------------------------ | --------------------------------- | ------------------------- |
| `SYSTEMATIC_REVIEW_EXPERT`     | PRISMA-compliant review expert    | Formal systematic reviews |
| `RESEARCH_ASSISTANT`           | General research assistant        | Discovery, exploration    |
| `META_ANALYSIS_ASSISTANT`      | Quantitative synthesis specialist | Effect sizes, pooling     |
| `LITERATURE_MAPPING_ASSISTANT` | Bibliometric analysis expert      | Mapping reviews, networks |

### MegaPrompts Constants

| Constant                          | Description                 | Duration  |
| --------------------------------- | --------------------------- | --------- |
| `COMPREHENSIVE_LITERATURE_REVIEW` | Full SLR workflow           | 1-2 weeks |
| `RESEARCH_BASELINE`               | Foundational knowledge      | 2-3 days  |
| `COMPARATIVE_ANALYSIS`            | Approach comparison         | 1-2 days  |
| `GAP_ANALYSIS`                    | Research gap identification | 1-2 days  |
| `META_ANALYSIS_PROTOCOL`          | Protocol design             | 1-2 days  |
| `EXPERT_INTERVIEW_PREPARATION`    | Interview guide creation    | 4-8 hours |
| `RESEARCH_PROPOSAL_FOUNDATION`    | Proposal literature section | 2-3 days  |
| `ANNOTATED_BIBLIOGRAPHY`          | Structured annotations      | 1-2 days  |

### PromptHelpers Functions

| Function             | Description                           |
| -------------------- | ------------------------------------- |
| `buildSearchQuery()` | Construct search queries with filters |
| `formatForPRISMA()`  | Format PRISMA flow diagram data       |

---

## License

Part of the Nexus PHP library. See main LICENSE file.
