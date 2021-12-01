<?php

namespace TNM\Utilities\LogPurger\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TNM\Utilities\LogPurger\LogPurgerServiceProvider;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(realpath(__DIR__.'/../src/database/migrations'));
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../src/database/test_migrations'));

    }

    protected function getPackageProviders($app): array
    {
        return [
            LogPurgerServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }
}