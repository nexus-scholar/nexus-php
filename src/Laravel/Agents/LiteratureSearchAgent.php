<?php

namespace Nexus\Laravel\Agents;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Nexus\Core\NexusService;
use Nexus\Laravel\Jobs\SearchJob;
use Nexus\Laravel\NexusConfig;
use Nexus\Laravel\NexusSearcher;
use Nexus\Laravel\Tools\LiteratureSearchTool;
use Nexus\Models\Document;
use Nexus\Models\Query;

class LiteratureSearchAgent implements Agent
{
    protected string $instructions;

    protected ?Collection $messages = null;

    protected array $tools = [];

    protected ?string $defaultProvider = null;

    protected int $defaultMaxResults = 20;

    protected bool $includeAbstract = true;

    protected bool $includeAuthors = true;

    public function __construct(?string $instructions = null)
    {
        $this->instructions = $instructions ?? $this->getDefaultInstructions();
        $this->initializeTools();
    }

    public static function make(?string $instructions = null): self
    {
        return new self($instructions);
    }

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function messages(): iterable
    {
        return $this->messages ?? [];
    }

    public function tools(): iterable
    {
        return $this->tools;
    }

    public function withInstructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function withTools(array $tools): self
    {
        $this->tools = $tools;

        return $this;
    }

    public function withTool(LiteratureSearchTool $tool): self
    {
        $this->tools = [$tool];

        return $this;
    }

    public function withProvider(?string $provider): self
    {
        $this->defaultProvider = $provider;

        return $this;
    }

    public function withMaxResults(int $maxResults): self
    {
        $this->defaultMaxResults = $maxResults;

        return $this;
    }

    public function withAbstract(bool $include): self
    {
        $this->includeAbstract = $include;
        if (isset($this->tools[0]) && $this->tools[0] instanceof LiteratureSearchTool) {
            $this->tools[0]->withAbstract($include);
        }

        return $this;
    }

    public function withAuthors(bool $include): self
    {
        $this->includeAuthors = $include;
        if (isset($this->tools[0]) && $this->tools[0] instanceof LiteratureSearchTool) {
            $this->tools[0]->withAuthors($include);
        }

        return $this;
    }

    public function prompt(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): AgentResponse {
        $query = $this->parseQueryFromPrompt($prompt);

        if ($query === null) {
            return $this->createEmptyResponse('No valid search query could be extracted from the prompt.');
        }

        $searcher = $this->resolveSearcher();
        $results = $searcher->search($query, $this->getProviders($provider));

        return $this->createResponse($results, $query);
    }

    public function stream(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        $query = $this->parseQueryFromPrompt($prompt);
        $searcher = $this->resolveSearcher();
        $results = $query !== null
            ? $searcher->search($query, $this->getProviders($provider))
            : [];

        $output = $this->formatResults($results, $query);

        return new StreamableAgentResponse(
            invocationId: uniqid('nexus_'),
            generator: function () use ($output): iterable {
                yield new TextDelta($output, 'block_1', 'nexus', 'literature-search');
            },
            meta: new Meta(
                provider: 'nexus',
                model: 'literature-search'
            )
        );
    }

    public function queue(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        $query = $this->parseQueryFromPrompt($prompt);
        $queryData = $query?->toArray() ?? [];
        $providers = $this->getProviders($provider);

        $job = new SearchJob(
            new Query(
                text: $queryData['text'] ?? '',
                maxResults: $queryData['max_results'] ?? $this->defaultMaxResults,
                yearMin: $queryData['year_min'] ?? null,
                yearMax: $queryData['year_max'] ?? null
            ),
            $providers ?? []
        );

        return new QueuedAgentResponse(dispatch($job));
    }

    public function broadcast(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        bool $now = false,
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->stream($prompt, $attachments, $provider, $model);
    }

    public function broadcastNow(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->stream($prompt, $attachments, $provider, $model);
    }

    public function broadcastOnQueue(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        return $this->queue($prompt, $attachments, $provider, $model);
    }

    public function search(Query $query, ?array $providers = null): Collection
    {
        $searcher = $this->resolveSearcher();

        return new Collection($searcher->search($query, $providers ?? $this->getProviders($providers)));
    }

    protected function getDefaultInstructions(): string
    {
        return <<<'INSTRUCTIONS'
You are a literature research assistant specialized in systematic literature reviews.
Your role is to help researchers find relevant academic papers and articles.

Capabilities:
- Search across multiple academic databases (OpenAlex, Crossref, arXiv, Semantic Scholar, PubMed, DOAJ, IEEE)
- Find papers matching specific research queries
- Filter results by year, language, and citation count
- Extract key information from papers including titles, authors, abstracts, and URLs

Guidelines:
- Always use precise search terms for better results
- Suggest relevant keywords if the initial query is too broad
- Summarize findings concisely when presenting results
- Note any access restrictions or preprint status
- Recommend follow-up searches if initial results are insufficient

When searching, prefer specific technical terms over general keywords.
INSTRUCTIONS;
    }

    protected function initializeTools(): void
    {
        $tool = LiteratureSearchTool::make()
            ->withAbstract($this->includeAbstract)
            ->withAuthors($this->includeAuthors);

        if ($this->defaultProvider !== null) {
            $tool->withProviders([$this->defaultProvider]);
        }

        $this->tools = [$tool];
    }

    protected function parseQueryFromPrompt(string $prompt): ?Query
    {
        $prompt = trim($prompt);

        if (empty($prompt)) {
            return null;
        }

        return new Query(
            text: $prompt,
            maxResults: $this->defaultMaxResults,
            yearMin: null,
            yearMax: null,
            language: 'en'
        );
    }

    protected function getProviders(Lab|array|string|null $provider): ?array
    {
        if ($provider === null) {
            return $this->defaultProvider !== null ? [$this->defaultProvider] : null;
        }

        if (is_array($provider)) {
            return $provider;
        }

        if (is_string($provider)) {
            return [$provider];
        }

        return null;
    }

    protected function resolveSearcher(): NexusSearcher
    {
        $app = app();

        return new NexusSearcher(
            $app->make(NexusService::class),
            NexusConfig::fromLaravelConfig(),
            $app->make('cache.store')
        );
    }

    protected function createResponse(array $results, Query $query): AgentResponse
    {
        $output = $this->formatResults($results, $query);

        $usage = new Usage(
            promptTokens: strlen($query->text),
            completionTokens: strlen($output)
        );

        $citations = $this->extractCitations($results);

        $meta = new Meta(
            provider: 'nexus',
            model: 'literature-search',
            citations: new Collection($citations)
        );

        return new AgentResponse(
            invocationId: uniqid('nexus_'),
            text: $output,
            usage: $usage,
            meta: $meta
        );
    }

    protected function createEmptyResponse(string $message): AgentResponse
    {
        $usage = new Usage;
        $meta = new Meta(provider: 'nexus', model: 'literature-search');

        return new AgentResponse(
            invocationId: uniqid('nexus_'),
            text: $message,
            usage: $usage,
            meta: $meta
        );
    }

    protected function formatResults(array $results, ?Query $query): string
    {
        if ($results === []) {
            return $query !== null
                ? "No literature found for query: \"{$query->text}\". Try different keywords."
                : 'No results available.';
        }

        $count = count($results);
        $queryText = $query?->text ?? 'search';

        $lines = ["Found {$count} result(s) for \"{$queryText}\":\n"];

        foreach ($results as $index => $doc) {
            $index = $index + 1;
            $lines[] = $this->formatDocument($doc, $index);
        }

        return implode("\n\n", $lines);
    }

    protected function formatDocument(Document $doc, int $index): string
    {
        $authors = $this->formatAuthors($doc->authors ?? []);
        $citations = $doc->citedByCount !== null ? " (Citations: {$doc->citedByCount})" : '';

        $line = "[{$index}] {$doc->title}\n";
        $line .= "    Authors: {$authors}\n";
        $line .= "    Year: {$doc->year} | Source: {$doc->venue} {$citations}\n";
        $line .= "    URL: {$doc->url}";

        if ($this->includeAbstract && $doc->abstract) {
            $abstract = mb_substr(strip_tags($doc->abstract), 0, 300);
            if (strlen($doc->abstract) > 300) {
                $abstract .= '...';
            }
            $line .= "\n    Abstract: {$abstract}";
        }

        return $line;
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

    protected function extractCitations(array $results): array
    {
        return array_map(function (Document $doc) {
            return [
                'title' => $doc->title,
                'url' => $doc->url,
                'provider' => $doc->provider,
                'year' => $doc->year,
                'authors' => array_map(fn ($a) => $a->name, $doc->authors ?? []),
            ];
        }, $results);
    }
}
