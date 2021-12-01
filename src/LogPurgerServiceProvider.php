<?php

namespace TNM\Utilities\LogPurger;

use Illuminate\Support\ServiceProvider;
use TNM\Utilities\LogPurger\Commands\PurgeLogsCommand;

class LogPurgerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeLogsCommand::class
            ]);

            $this->publishes([
                __DIR__.'/config/config.php' => config_path('purger.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'purger');
    }
}