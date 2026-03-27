<?php

namespace Nexus\Prompts;

class MegaPrompts
{
    public const COMPREHENSIVE_LITERATURE_REVIEW = <<<'PROMPT'
Conduct a comprehensive systematic literature review on the following research topic:

**RESEARCH QUESTION**: {query}

**OBJECTIVE**: Identify, analyze, and synthesize all relevant academic literature on this topic to understand the current state of knowledge, key debates, methodological approaches, and research gaps.

## PHASE 1: SEARCH STRATEGY DEVELOPMENT

### 1.1 Keyword Identification
- Primary keywords (mandatory in all searches)
- Secondary keywords (important but not essential)
- Synonyms and related terms
- Boolean combinations to use

### 1.2 Database Selection
Select the most appropriate databases for this topic:
- OpenAlex (comprehensive, open access friendly)
- Crossref (publisher-agnostic coverage)
- arXiv (preprints, especially for CS, physics, math)
- Semantic Scholar (AI-enhanced discovery)
- PubMed (biomedical, health sciences)
- DOAJ (open access journals)
- IEEE (engineering, computer science)

### 1.3 Search String Construction
Develop optimized search strings for each database, including:
- Title/abstract searches
- Full-text searches (if needed)
- Date restrictions
- Language filters

## PHASE 2: SEARCH EXECUTION

Execute searches across all selected databases and document:
- Number of results per database
- Search date
- Any database-specific adaptations

## PHASE 3: SCREENING AND SELECTION

### Inclusion Criteria
- Language: [Specify]
- Date range: [Specify]
- Publication type: [Specify]
- Study design: [Specify]

### Exclusion Criteria
- [List specific exclusions]

### Selection Process
- Total records identified
- After duplicate removal
- After title/abstract screening
- After full-text assessment
- Final included studies

## PHASE 4: DATA EXTRACTION

For each included study, extract:
- Bibliographic information
- Study design/methodology
- Key findings
- Limitations
- Relevance to research question

## PHASE 5: SYNTHESIS

### Thematic Analysis
Group findings into major themes:
1. [Theme 1]
2. [Theme 2]
3. [Theme 3]

### Methodological Trends
- Dominant methodologies
- Emerging methods
- Measurement approaches

### Gap Analysis
- What is well-established
- What is emerging/controversial
- What is missing

## OUTPUT REQUIREMENTS

Provide:
1. Executive summary
2. Search strategy documentation
3. PRISMA flow diagram data
4. Evidence tables
5. Thematic synthesis
6. Research gap identification
7. Recommendations for future research
PROMPT;

    public const RESEARCH_BASELINE = <<<'PROMPT'
Establish a comprehensive research baseline for: **{query}**

This baseline should serve as the foundational knowledge for a research project, ensuring complete understanding of the field's current state.

## COMPONENT 1: FOUNDATIONAL WORKS

Identify the 10-15 most influential papers in this field:
- Origin papers that established the field
- Paradigm-shifting contributions
- Papers with highest citation impact
- Methodological foundations

For each paper provide:
- Full citation
- Why it's foundational
- Key contributions
- Current relevance

## COMPONENT 2: THEORETICAL FRAMEWORK

Map the major theoretical perspectives:
- Core theories and their proponents
- How theories relate to each other
- Evolution of theoretical thinking
- Current theoretical debates

## COMPONENT 3: METHODOLOGICAL LANDSCAPE

Document research methods used in this field:
- Dominant methodologies
- Data collection approaches
- Analysis techniques
- Emerging methods
- Measurement instruments

## COMPONENT 4: EMPIRICAL FINDINGS

Synthesize key empirical findings:
- Well-established facts
- Consistent patterns
- Contradictory findings
- Effect sizes where available

## COMPONENT 5: KNOWLEDGE GAPS

Identify:
- Unanswered questions
- Understudied populations/contexts
- Methodological limitations in current research
- Theoretical gaps

## COMPONENT 6: RESEARCH FRONTIERS

Document:
- Emerging topics and trends
- New methodological approaches
- Interdisciplinary connections
- Predicted future directions

## OUTPUT FORMAT

Structure as a research handbook with:
- Executive summary
- Detailed sections for each component
- Annotated bibliography
- Visual concept maps (described textually)
- Prioritized reading list
PROMPT;

    public const COMPARATIVE_ANALYSIS = <<<'PROMPT'
Perform a comparative analysis of approaches to: **{query}**

## OBJECTIVE

Compare and contrast different approaches, methods, or perspectives on this topic to understand their similarities, differences, strengths, and limitations.

## ANALYSIS FRAMEWORK

### Dimension 1: Theoretical Foundations
- What are the different theoretical bases?
- How do they explain the phenomenon?
- Which is most empirically supported?

### Dimension 2: Methodological Approaches
- What methods are used by each approach?
- How do they differ in data collection?
- What are the analytical techniques?

### Dimension 3: Scope and Application
- What phenomena does each approach explain?
- In what contexts are they most useful?
- What are their boundaries/limitations?

### Dimension 4: Evidence Base
- What empirical support exists for each?
- What are the effect sizes?
- How consistent are findings?

### Dimension 5: Practical Implications
- What do practitioners need to know?
- How actionable are recommendations?
- What are implementation considerations?

## OUTPUT REQUIREMENTS

1. **Comparative Table**: Matrix showing each approach across dimensions
2. **Narrative Analysis**: Detailed comparison with examples
3. **Synthesis**: Key insights from the comparison
4. **Recommendations**: When to use which approach
5. **Limitations**: Gaps in the comparative evidence
6. **Future Directions**: How the approaches might converge or diverge

## SYNTHESIS QUESTIONS

Address:
- What can we conclude from comparing these approaches?
- Where do they agree? Disagree?
- What new insights emerge from the comparison?
- What remains unresolved?
PROMPT;

    public const GAP_ANALYSIS = <<<'PROMPT'
Conduct a systematic gap analysis for research on: **{query}**

## PURPOSE

Identify what is known, what is unknown, and what needs to be investigated to advance knowledge in this field.

## METHODOLOGY

### Step 1: Scope Definition
- Define the boundaries of the research area
- Identify related but distinct topics
- Establish inclusion/exclusion criteria

### Step 2: Knowledge Mapping
Create a comprehensive map of existing knowledge:
- Major topics covered
- Underrepresented topics
- Emerging areas
- Declining areas

### Step 3: Gap Identification

#### Theoretical Gaps
- Missing conceptual frameworks
- Unintegrated theories
- Untested theoretical propositions

#### Methodological Gaps
- Techniques not yet applied
- Populations not yet studied
- Contexts not yet examined
- Measurement limitations

#### Empirical Gaps
- Unanswered research questions
- Understudied relationships
- Missing replication studies
- Inconsistent findings unexplained

#### Practical Gaps
- Unaddressed practitioner needs
- Translation issues
- Implementation barriers

### Step 4: Gap Prioritization

Evaluate gaps based on:
- Scientific importance
- Practical relevance
- Feasibility of addressing
- Potential impact

### Step 5: Research Agenda

Develop a prioritized research agenda:
- Immediate priorities
- Medium-term objectives
- Long-term goals

## OUTPUT COMPONENTS

1. **Gap Taxonomy**: Categorized list of all identified gaps
2. **Gap Matrix**: Prioritization by importance vs. feasibility
3. **Gap Narratives**: Detailed descriptions of key gaps
4. **Research Questions**: Specific questions to address each gap
5. **Methodological Recommendations**: How to address each gap
6. **Timeline**: Phased research agenda

## VALIDATION

Cross-validate with:
- Expert opinion
- Recent review papers
- Editorial pieces on future directions
- Grant priority statements
PROMPT;

    public const META_ANALYSIS_PROTOCOL = <<<'PROMPT'
Design a meta-analysis protocol for: **{query}**

## PROTOCOL COMPONENTS

### 1. BACKGROUND AND RATIONALE

- What is the empirical problem?
- Why is a meta-analysis needed now?
- What have previous meta-analyses found?
- How will this advance knowledge?

### 2. REVIEW QUESTIONS

Primary question:
[Specific, measurable question]

Secondary questions:
[Related sub-questions]

### 3. INCLUSION CRITERIA

#### Study Characteristics
- Study designs to include
- Publication types (journals, dissertations, preprints)
- Date range
- Language restrictions

#### Population
- Target population
- Inclusion characteristics
- Exclusion characteristics

#### Intervention/Exposure
- Definition of intervention/exposure
- Comparison conditions
- Minimum duration/dose

#### Outcome
- Primary outcome(s)
- How outcomes will be measured
- Time points of interest

### 4. SEARCH STRATEGY

#### Databases to Search
- [List with rationale]
- Search date
- Date restrictions

#### Search Terms
Provide detailed search strings for:
- Primary database (e.g., PubMed)
- Secondary databases

#### Additional Sources
- Reference tracking
- Expert consultation
- Gray literature sources

### 5. STUDY SELECTION

#### Process
- Number of reviewers
- Duplicate screening
- Conflict resolution

#### PRISMA Flow
Document:
- Initial results
- After duplicates
- After screening
- After eligibility
- Final sample

### 6. DATA EXTRACTION

#### Data Items
- Study characteristics
- Participant characteristics
- Intervention details
- Outcome data
- Effect sizes
- Variance measures

#### Quality Assessment
- Risk of bias tool
- Quality dimensions
- How quality affects analysis

### 7. DATA SYNTHESIS

#### Effect Size Calculation
- Effect size metric (Cohen's d, OR, RR, etc.)
- How to handle different metrics
- How to handle missing data

#### Heterogeneity Analysis
- Statistical tests (Q, I²)
- Subgroup analyses
- Meta-regression variables

#### Publication Bias
- Funnel plot
- Egger's test
- Trim-and-fill
- Fail-safe N

#### Sensitivity Analyses
- Outlier detection
- Influence analysis
- Model robustness

### 8. ANALYSIS PLAN

Step-by-step statistical analysis:
1. Data preparation
2. Effect size computation
3. Pooled estimate
4. Heterogeneity assessment
5. Subgroup analyses
6. Meta-regression
7. Publication bias
8. Sensitivity analyses

### 9. REPORTING

Follow PRISMA guidelines:
- PRISMA checklist
- PRISMA flow diagram
- Forest plots
- Summary of findings tables

### 10. TIMELINE

| Phase | Duration |
|-------|----------|
| Search | X weeks |
| Screening | X weeks |
| Extraction | X weeks |
| Analysis | X weeks |
| Writing | X weeks |
PROMPT;

    public const EXPERT_INTERVIEW_PREPARATION = <<<'PROMPT'
Prepare for expert interviews on: **{query}**

## PURPOSE

Create a structured interview guide based on current literature to maximize the value of expert consultations.

## LITERATURE SYNTHESIS FIRST

Before developing questions, review:
- Key theoretical debates
- Methodological controversies
- Unresolved empirical questions
- Practical challenges noted in literature

## INTERVIEW DOMAINS

### Domain 1: Theoretical Perspectives
Questions about underlying theories and models:
1. [Derived from theoretical gaps]
2. [Derived from debates]
3. [Derived from recent developments]

### Domain 2: Empirical Evidence
Questions about research findings:
1. [Derived from inconsistent findings]
2. [Derived from missing replication]
3. [Derived from practical implementation gaps]

### Domain 3: Methodological Issues
Questions about research approaches:
1. [Derived from methodological gaps]
2. [Derived from emerging methods]
3. [Derived from measurement issues]

### Domain 4: Practical Implementation
Questions about real-world application:
1. [Derived from practice gaps]
2. [Derived from translation issues]
3. [Derived from practitioner needs]

### Domain 5: Future Directions
Questions about research frontier:
1. [Emerging topics]
2. [Predicted developments]
3. [Priority areas]

## INTERVIEW STRUCTURE

### Opening (5 minutes)
- Purpose explanation
- Consent confirmation
- Background of expert

### Main Interview (45-60 minutes)
- Domain 1 questions
- Domain 2 questions
- Domain 3 questions
- Domain 4 questions
- Domain 5 questions

### Closing (5-10 minutes)
- Additional thoughts
- Recommended contacts
- Follow-up possibilities

## QUESTION TYPES

### Factual Probes
- What is your perspective on [specific finding]?
- Can you explain [theoretical concept]?

### Opinion Probes
- In your view, what is the most important [issue]?
- Where do you disagree with the prevailing view?

### Experience Probes
- Based on your experience, what works best?
- What challenges have you encountered?

### Future Probes
- What do you see as the next major development?
- What should researchers prioritize?

## DOCUMENTATION

Prepare:
- Consent form
- Recording preferences
- Note-taking template
- Follow-up email template
PROMPT;

    public const RESEARCH_PROPOSAL_FOUNDATION = <<<'PROMPT'
Build a literature foundation for a research proposal on: **{query}**

## PROPOSAL COMPONENT MAPPING

### 1. PROBLEM STATEMENT

Based on literature:
- What is the specific problem addressed?
- Who experiences this problem?
- What are the consequences?

**Literature sources needed**: Problem prevalence studies, burden of disease, practical impact reports

### 2. SIGNIFICANCE

Why does this research matter?

**Literature sources needed**: Importance studies, trend analyses, expert calls for research

### 3. THEORETICAL FRAMEWORK

What theory guides this research?

**Literature sources needed**: Theoretical papers, framework validations, conceptual models

### 4. LITERATURE REVIEW

What does existing research show?

**Required sections**:
- Historical development
- Current state of knowledge
- Key findings and patterns
- Methodological approaches used
- Settings/contexts studied

### 5. KNOWLEDGE GAPS

What remains unknown?

**Literature sources needed**: Systematic reviews, gap analyses, calls for research

### 6. RESEARCH QUESTIONS/HYPOTHESES

What specifically will be investigated?

**Literature sources needed**: Unanswered questions, contradictory findings, exploratory needs

### 7. METHODOLOGY SELECTION

Why is this approach appropriate?

**Literature sources needed**: Methodological comparisons, validation studies, precedent studies

### 8. EXPECTED CONTRIBUTIONS

What will this add to knowledge?

**Literature sources needed**: Gap identification, potential impact analyses

## DELIVERABLE: LITERATURE REVIEW STRUCTURE

### Section 1: Introduction
- Scope and organization
- Search strategy summary
- Overview of included literature

### Section 2: Historical Context
- Origins of the field/topic
- Major paradigm shifts
- Current dominant approaches

### Section 3: Theoretical Foundations
- Major theories and models
- Conceptual frameworks
- Theoretical debates

### Section 4: Empirical Evidence
- Summary of key findings
- Effect sizes and patterns
- Consistency of evidence

### Section 5: Methodological Approaches
- Common methods
- Measurement approaches
- Emerging methods

### Section 6: Contextual Factors
- Settings studied
- Populations examined
- Cultural/geographic considerations

### Section 7: Synthesis
- What is well-established
- What is emerging
- What is contested

### Section 8: Research Gaps
- Theoretical gaps
- Empirical gaps
- Methodological gaps
- Practical gaps

### Section 9: Proposed Research
- How this addresses gaps
- Theoretical contribution
- Practical implications

## LITERATURE TRACKING

Create a tracking system for:
- Core citations for proposal
- Quotable passages
- Methodological details
- Effect sizes for power analysis
- Expert recommendations
PROMPT;

    public const ANNOTATED_BIBLIOGRAPHY = <<<'PROMPT'
Create an annotated bibliography for: **{query}**

## ANNOTATION REQUIREMENTS

For each source, provide:

### Bibliographic Information
- Full APA citation
- Database/source
- DOI if available

### Study Characteristics
- Study type/design
- Sample size and characteristics
- Setting/context
- Intervention/exposure (if applicable)
- Outcome measures
- Analysis approach

### Key Findings
- Main results (quantitative when possible)
- Statistical significance
- Effect sizes
- Direction of effects

### Critical Analysis
- Strengths
- Limitations
- Methodological concerns
- Potential biases

### Relevance
- How it addresses the research question
- Contribution to the field
- Implications for current research

### Quotable Passages
- Key quotes with page numbers
- Verbatim findings
- Theoretical statements

## ORGANIZATION

### By Theme/Topic
Group annotations by:
1. [Theme 1]
2. [Theme 2]
3. [Theme 3]

### Within Each Theme
- Foundational works (earliest, most cited)
- Recent work (last 5 years)
- Methodological papers
- Reviews and syntheses

## QUALITY INDICATORS

Note for each source:
- Peer-review status
- Impact factor (if available)
- Citation count (if available)
- Open access status
- Reproducibility (data/code availability)

## SYNTHESIS OPPORTUNITIES

After each theme section, note:
- Common findings across studies
- Inconsistencies or contradictions
- Emerging patterns
- Gaps within theme
PROMPT;
}
