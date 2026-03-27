<?php

namespace Nexus\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Nexus\Core\NexusService;
use Nexus\Core\ProviderFactory;
use Nexus\Laravel\Events\SearchCompleted;
use Nexus\Laravel\Events\SearchFailed;
use Nexus\Laravel\NexusConfig;
use Nexus\Models\Query;

class SearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 300;

    public int $memory = 512;

    private ?string $jobId = null;

    public function __construct(
        public Query $query,
        public array $providers,
        public ?string $userId = null,
        public ?string $externalJobId = null
    ) {}

    public function handle(NexusConfig $config): void
    {
        $this->jobId = $this->externalJobId ?? $this->job?->uuid() ?? md5($this->query->text.time());

        Log::info('SearchJob started', [
            'external_job_id' => $this->externalJobId,
            'job_uuid' => $this->job?->uuid(),
            'computed_job_id' => $this->jobId,
            'query' => $this->query->text,
            'providers' => $this->providers,
        ]);

        $this->updateProgress('searching', 0);

        $startTime = microtime(true);
        $service = new NexusService;

        $providersToSearch = array_filter($this->providers, fn ($p) => $p !== 'core');
        $totalProviders = count($providersToSearch);
        $completedProviders = 0;
        $allResults = [];

        foreach ($providersToSearch as $name) {
            $this->updateProviderProgress($name, 'searching', 0);

            try {
                $provider = ProviderFactory::makeFromConfig($name, $config);
                $service->registerProvider($provider);

                $count = 0;
                $maxResults = $this->query->maxResults ?? 20;

                foreach ($provider->search($this->query) as $result) {
                    $allResults[] = $result;
                    $count++;
                    if ($count >= $maxResults) {
                        break;
                    }
                }

                $this->updateProviderProgress($name, 'completed', 100);
            } catch (\Throwable $e) {
                Log::error('Provider search failed', [
                    'provider' => $name,
                    'error' => $e->getMessage(),
                ]);
                $this->updateProviderProgress($name, 'failed', 0);
            }

            $completedProviders++;
            $progress = (int) (($completedProviders / $totalProviders) * 100);
            $this->updateProgress('searching', $progress);
        }

        $duration = microtime(true) - $startTime;

        $serializedResults = array_map(function ($doc) {
            return [
                'id' => $doc->providerId ?? $doc->externalIds->doi ?? uniqid(),
                'title' => $doc->title,
                'provider' => $doc->provider,
                'year' => $doc->year,
                'doi' => $doc->externalIds->doi,
                'url' => $doc->url,
                'venue' => $doc->venue,
                'authors' => array_map(fn ($a) => $a->getFullName(), $doc->authors),
                'abstract' => $doc->abstract,
                'cited_by_count' => $doc->citedByCount,
                'language' => $doc->language,
            ];
        }, $allResults);

        Cache::put("nexus:search:{$this->jobId}:status", [
            'status' => 'completed',
            'progress' => 100,
            'providers' => $this->getProviderStatuses(),
            'completed_at' => now()->toIso8601String(),
        ], $config->cache_ttl);

        Cache::put("nexus:search:{$this->jobId}:result", [
            'documents' => $serializedResults,
            'total' => count($serializedResults),
            'duration_ms' => round($duration * 1000),
        ], $config->cache_ttl);

        Log::info('SearchJob completed', [
            'job_id' => $this->jobId,
            'result_cache_key' => "nexus:search:{$this->jobId}:result",
            'doc_count' => count($serializedResults),
        ]);

        SearchCompleted::dispatch($this->query, $this->providers, $allResults, $duration, $this->userId);
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->jobId) {
            Cache::put("nexus:search:{$this->jobId}:status", [
                'status' => 'failed',
                'progress' => 0,
                'providers' => $this->getProviderStatuses(),
                'error' => $exception->getMessage(),
            ], 3600);
        }

        SearchFailed::dispatch(
            $this->query,
            $this->providers,
            $exception->getMessage(),
            $this->userId
        );
    }

    private function updateProgress(string $status, int $progress): void
    {
        if (! $this->jobId) {
            return;
        }

        $current = Cache::get("nexus:search:{$this->jobId}:status", []);
        Cache::put("nexus:search:{$this->jobId}:status", array_merge($current, [
            'status' => $status,
            'progress' => $progress,
        ]), 3600);
    }

    private function updateProviderProgress(string $provider, string $status, int $progress): void
    {
        if (! $this->jobId) {
            return;
        }

        $current = Cache::get("nexus:search:{$this->jobId}:status", []);
        $providers = $current['providers'] ?? [];
        $providers[$provider] = ['status' => $status, 'progress' => $progress];

        Cache::put("nexus:search:{$this->jobId}:status", array_merge($current, [
            'providers' => $providers,
        ]), 3600);
    }

    private function getProviderStatuses(): array
    {
        $statuses = [];
        foreach ($this->providers as $name) {
            $statuses[$name] = ['status' => 'completed', 'progress' => 100];
        }

        return $statuses;
    }

    private function getCacheKey(): string
    {
        return 'nexus:job:'.$this->job?->uuid() ?? md5($this->query->text);
    }

    public function getCacheKeyResult(): string
    {
        return 'nexus:job:'.($this->job?->uuid() ?? md5($this->query->text)).':result';
    }
}
