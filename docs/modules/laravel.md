# Laravel Module

The Laravel module provides framework-native integration for Nexus, including service container bindings, Artisan commands, queue jobs, events, listeners, tools, and AI agents.

## Files

- `src/Laravel/NexusServiceProvider.php`
- `src/Laravel/NexusSearcher.php`
- `src/Laravel/Commands/SearchCommand.php`
- `src/Laravel/Commands/SkillsCommand.php`
- `src/Laravel/Agents/LiteratureSearchAgent.php`
- `src/Laravel/Tools/LiteratureSearchTool.php`
- `src/Laravel/Jobs/SearchJob.php`
- `src/Laravel/Events/*`
- `src/Laravel/Listeners/*`
- `config/nexus-laravel.php`

## Installation

```bash
composer require nexus/nexus-php
php artisan vendor:publish --tag=nexus-config
```

## Container bindings

The service provider typically registers:

- `Nexus\Core\NexusService`
- `Nexus\Laravel\NexusSearcher`
- `nexus.searcher`

## Artisan commands

### Search command

```bash
php artisan nexus:search "plant disease detection" --providers=openalex,arxiv --format=json
```

### Skills command

```bash
php artisan nexus:skills
php artisan nexus:skills --json
php artisan nexus:skills --skill=search
```

## Using the searcher in application code

```php
use Nexus\Laravel\NexusSearcher;

class LiteratureController
{
    public function __construct(private NexusSearcher $searcher) {}

    public function __invoke()
    {
        $results = $this->searcher->search('plant disease detection');
        return response()->json($results);
    }
}
```

## AI integration

### `LiteratureSearchTool`

Attach this tool to a Laravel AI agent when you want general agents to access scholarly search.

### `LiteratureSearchAgent`

Use this when you want a dedicated literature-search agent with prompt-driven workflows.

## Queues and events

Use queued search jobs and event listeners when searches may be long-running or when you want logging, notifications, or persistence hooks.

## Best practices

- Publish and review config before production use.
- Queue large searches.
- Cache provider responses when acceptable.
- Use events for observability.
