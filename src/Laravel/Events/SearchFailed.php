<?php

namespace Nexus\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Nexus\Models\Query;
use Illuminate\Queue\SerializesModels;

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
