<?php

namespace Nexus\Laravel\Commands;

use Illuminate\Console\Command;

class SkillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:skills {--json : Output in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available agent skills in the Nexus Research Engine';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $args = [];
        if ($this->option('json')) {
            $args[] = '--json';
        }

        $scriptPath = realpath(__DIR__.'/../../../nexus-skills/discover.php');

        if (! $scriptPath) {
            $this->error('Failed to locate discover.php script.');

            return self::FAILURE;
        }

        $cmd = 'php '.escapeshellarg($scriptPath).($args ? ' '.implode(' ', $args) : '');
        passthru($cmd, $resultCode);

        return $resultCode;
    }
}
