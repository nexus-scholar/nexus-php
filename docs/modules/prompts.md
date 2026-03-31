# Prompts Module

The Prompts module contains reusable prompt assets designed for AI-assisted systematic literature review workflows.

## Files

- `src/Prompts/SystemPrompts.php`
- `src/Prompts/MegaPrompts.php`
- `src/Prompts/PromptHelpers.php`
- `src/Prompts/README.md`

## Use cases

- Literature screening instructions for agents.
- Research-gap analysis.
- Bibliometric reporting.
- Structured synthesis and summarization.
- Prompt composition for Laravel AI agents.

## Example usage

```php
use Nexus\Prompts\SystemPrompts;

$prompt = SystemPrompts::literatureSearchAssistant();
```

Or, if your implementation exposes helper builders:

```php
$prompt = PromptHelpers::buildScreeningPrompt(
    topic: 'plant disease detection',
    inclusionCriteria: ['peer reviewed', '2020-2026'],
    exclusionCriteria: ['non-English'],
);
```

## Guidance

- Treat prompts as versioned assets.
- Keep business logic outside prompts.
- Expose overridable prompt hooks when integrating with Laravel AI or custom agents.
- Document output schemas expected by downstream code.
