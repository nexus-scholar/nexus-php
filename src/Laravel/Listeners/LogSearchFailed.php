<?php

namespace Nexus\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Nexus\Laravel\Events\SearchFailed;
use Psr\Log\LoggerInterface;

class LogSearchFailed implements ShouldQueue
{
    public function handle(SearchFailed $event): void
    {
        $logger = app(LoggerInterface::class);
        $logger->error('Nexus search failed', [
            'query' => $event->query->text,
            'providers' => $event->providers,
            'error' => $event->error,
            'user_id' => $event->userId,
        ]);
    }
}
