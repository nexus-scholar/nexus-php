<?php

namespace Nexus\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Nexus\Laravel\Events\SearchCompleted;
use Psr\Log\LoggerInterface;

class LogSearchCompleted implements ShouldQueue
{
    public function handle(SearchCompleted $event): void
    {
        $logger = app(LoggerInterface::class);
        $logger->info('Nexus search completed', [
            'query' => $event->query->text,
            'providers' => $event->providers,
            'result_count' => $event->getResultCount(),
            'unique_providers' => $event->getUniqueProviders(),
            'duration_seconds' => round($event->duration, 2),
            'user_id' => $event->userId,
        ]);
    }
}
