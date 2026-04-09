<?php

namespace Nexus\Laravel\Tools;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Nexus\Core\NexusService;
use Nexus\Laravel\NexusConfig;
use Nexus\Laravel\NexusSearcher;
use Nexus\Models\Document;
use Nexus\Models\Query;

class LiteratureSearchTool implements Tool
{
    protected string $description = 'Search academic literature databases for scientific papers and articles matching a research query.';

    protected ?NexusSearcher $searcherInstance = null;

    protected ?array $providers = null;

    protected bool $includeAbstract = true;

    protected bool $includeAuthors = true;

    public function __construct(
        protected ?Closure $customSearcher = null
    ) {}

    public static function make(?Closure $customSearcher = null): self
    {
        return new self($customSearcher);
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function withProviders(array $providers): self
    {
        $this->providers = $providers;

        return $this;
    }

    public function withAbstract(bool $include): self
    {
        $this->includeAbstract = $include;

        return $this;
    }

    public function withAuthors(bool $include): self
    {
        $this->includeAuthors = $include;

        return $this;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function handle(Request $request): string
    {
        $queryString = $request->string('query')->toString();

        if (empty(trim($queryString))) {
            return 'No search query provided. Please provide a research query to search for literature.';
        }

        $query = new Query(
            text: $queryString,
            maxResults: $request->integer('max_results', 20),
            yearMin: $request->integer('year_min'),
            yearMax: $request->integer('year_max'),
            language: $request->string('language', 'en')->toString()
        );

        $results = $this->executeSearch($query);

        if ($results->isEmpty()) {
            return "No literature found for query: \"{$queryString}\". Try different keywords or expand your search.";
        }

        return $this->formatResults($results, $queryString);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The research query to search for. Use specific keywords, boolean operators (AND, OR, NOT), or phrases in quotes for exact matching.')
                ->required(),
            'max_results' => $schema
                ->integer()
                ->description('Maximum number of results to return (default: 20).')
                ->default(20)
                ->minimum(1)
                ->maximum(100),
            'year_min' => $schema
                ->integer()
                ->description('Minimum publication year to filter results.')
                ->minimum(1900)
                ->maximum(2030),
            'year_max' => $schema
                ->integer()
                ->description('Maximum publication year to filter results.')
                ->minimum(1900)
                ->maximum(2030),
            'language' => $schema
                ->string()
                ->description('Language filter for results (default: en).')
                ->default('en')
                ->enum(['en', 'es', 'fr', 'de', 'zh', 'ja', 'pt', 'ru']),
        ];
    }

    protected function executeSearch(Query $query): Collection
    {
        if ($this->customSearcher !== null) {
            $results = call_user_func($this->customSearcher, $query, $this->providers);

            return match (true) {
                is_array($results) => new Collection($results),
                $results instanceof Collection => $results,
                default => new Collection([$results]),
            };
        }

        $searcher = $this->resolveSearcher();

        return new Collection($searcher->search($query, $this->providers));
    }

    protected function resolveSearcher(): NexusSearcher
    {
        if ($this->searcherInstance !== null) {
            return $this->searcherInstance;
        }

        $app = app();
        $this->searcherInstance = new NexusSearcher(
            $app->make(NexusService::class),
            NexusConfig::fromLaravelConfig(),
            $app->make('cache.store')
        );

        return $this->searcherInstance;
    }

    protected function formatResults(Collection $results, string $query): string
    {
        $formatted = $results->map(function (Document $doc, int $index) {
            $index = $index + 1;
            $authors = $this->includeAuthors
                ? $this->formatAuthors($doc->authors)
                : ($doc->authors !== [] ? 'Yes' : 'Unknown');

            $line = "[{$index}] {$doc->title}\n";
            $line .= "    Authors: {$authors}\n";
            $line .= "    Year: {$doc->year}\n";
            $line .= "    Source: {$doc->venue} ({$doc->provider})";

            if ($doc->citedByCount !== null) {
                $line .= " | Citations: {$doc->citedByCount}";
            }

            $line .= "\n    URL: {$doc->url}";

            if ($this->includeAbstract && $doc->abstract) {
                $abstract = Str::limit(strip_tags($doc->abstract), 300);
                $line .= "\n    Abstract: {$abstract}...";
            }

            return $line;
        })->join("\n\n");

        $count = $results->count();
        $providerList = $this->providers !== null
            ? implode(', ', $this->providers)
            : 'all enabled';

        return "Found {$count} literature result(s) for \"{$query}\" from {$providerList}:\n\n{$formatted}";
    }

    protected function formatAuthors(array $authors): string
    {
        if ($authors === []) {
            return 'Unknown';
        }

        $names = array_map(fn ($author) => $author->name, $authors);
        $first = $names[0] ?? 'Unknown';
        $count = count($names);

        if ($count === 1) {
            return $first;
        }

        if ($count === 2) {
            return "{$first} and {$names[1]}";
        }

        return "{$first} et al. ({$count} authors)";
    }
}
