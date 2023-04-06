<?php

namespace TNM\Utilities\LogPurger\Commands;

use Illuminate\Console\Command;
use TNM\Utilities\LogPurger\Services\LogPurgerService;

class PurgeLogsCommand extends Command
{
    protected $signature = 'utils:purge-logs {--limit=1000}';

    protected $description = 'Purge logs';

    public function handle(): int
    {
        return (new LogPurgerService($this->option('limit')))->query();
    }
}