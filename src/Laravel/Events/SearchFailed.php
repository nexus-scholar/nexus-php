<?php

namespace Nexus\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\Models\Query;

class SearchFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Query $query,
        public array $providers,
        public string $error,
        public ?string $userId = null
    ) {}
}
