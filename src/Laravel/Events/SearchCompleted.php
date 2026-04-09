<?php

namespace Nexus\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\Models\Document;
use Nexus\Models\Query;

class SearchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Query $query,
        public array $providers,
        public array $results,
        public float $duration,
        public ?string $userId = null
    ) {}

    public function getResultCount(): int
    {
        return count($this->results);
    }

    public function getUniqueProviders(): array
    {
        $providers = [];
        foreach ($this->results as $result) {
            if ($result instanceof Document) {
                $providers[$result->provider] = true;
            }
        }

        return array_keys($providers);
    }
}
