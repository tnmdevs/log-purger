<?php

namespace TNM\Utilities\LogPurger\Commands;

use Illuminate\Console\Command;
use TNM\Utilities\LogPurger\Services\LogPurgerService;

class PurgeLogsCommand extends Command
{
    protected $signature = 'utils:purge-logs';

    protected $description = 'Purge logs';

    public function handle(): int
    {
        return (new LogPurgerService())->query();
    }
}