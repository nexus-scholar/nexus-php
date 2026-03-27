<?php

namespace Nexus\Laravel\Events;

use Nexus\Models\Query;

class SearchStarted
{
    public function __construct(
        public Query $query,
        public array $providers,
        public ?string $userId = null
    ) {}
}
