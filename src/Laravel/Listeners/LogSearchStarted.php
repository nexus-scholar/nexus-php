<?php

namespace Nexus\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Nexus\Laravel\Events\SearchStarted;
use Psr\Log\LoggerInterface;

class LogSearchStarted implements ShouldQueue
{
    public function handle(SearchStarted $event): void
    {
        $logger = app(LoggerInterface::class);
        $logger->info('Nexus search started', [
            'query' => $event->query->text,
            'providers' => $event->providers,
            'max_results' => $event->query->maxResults,
            'year_range' => $event->query->yearMin && $event->query->yearMax
                ? "{$event->query->yearMin}-{$event->query->yearMax}"
                : null,
            'user_id' => $event->userId,
        ]);
    }
}
