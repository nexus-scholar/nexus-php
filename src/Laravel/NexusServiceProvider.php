<?php

namespace Nexus\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Nexus\Core\NexusService;
use Nexus\Laravel\Agents\LiteratureSearchAgent;
use Nexus\Laravel\Commands\SearchCommand;
use Nexus\Laravel\Commands\SkillsCommand;
use Nexus\Laravel\Events\SearchCompleted;
use Nexus\Laravel\Events\SearchStarted;
use Nexus\Laravel\Listeners\LogSearchCompleted;
use Nexus\Laravel\Listeners\LogSearchStarted;
use Nexus\Laravel\Tools\LiteratureSearchTool;

class NexusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/nexus-laravel.php',
            'nexus'
        );

        $this->app->singleton(NexusService::class, function ($app) {
            return new NexusService;
        });

        $this->app->singleton(NexusConfig::class, function ($app) {
            return NexusConfig::fromLaravelConfig();
        });

        $this->app->bind('nexus.searcher', function ($app) {
            return new NexusSearcher(
                $app->make(NexusService::class),
                $app->make(NexusConfig::class),
                $app->make('cache.store')
            );
        });

        $this->app->bind(NexusSearcher::class, function ($app) {
            return $app->make('nexus.searcher');
        });

        $this->app->bind(LiteratureSearchTool::class, function ($app) {
            return LiteratureSearchTool::make();
        });

        $this->app->bind(LiteratureSearchAgent::class, function ($app) {
            return LiteratureSearchAgent::make();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SearchCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../../config/nexus-laravel.php' => config_path('nexus.php'),
        ], 'nexus-config');

        $this->registerEvents();
    }

    private function registerEvents(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->listen(SearchStarted::class, LogSearchStarted::class);
        $events->listen(SearchCompleted::class, LogSearchCompleted::class);
    }
}
