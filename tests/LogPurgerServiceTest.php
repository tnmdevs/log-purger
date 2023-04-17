<?php

namespace TNM\Utilities\LogPurger\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use TNM\Utilities\LogPurger\Models\PurgingPolicy;
use TNM\Utilities\LogPurger\Models\Test;

class LogPurgerServiceTest extends TestCase
{
    public function test_purge_old_records()
    {
        Config::set('app.purge.disk', 'local');
        Test::create(['created_at' => today()->subDays(59)]);
        Test::create(['created_at' => today()->subDays(64)]);
        Test::create(['created_at' => today()->subDays(66)]);

        PurgingPolicy::create([
            'table' => 'tests',
            'days_to_live' => 60
        ]);


        Storage::fake(config('app.purge.disk'));

        $this->artisan('utils:purge-logs')->assertExitCode(0)->run();
        $this->assertDatabaseCount('tests', 1);

        $this->assertDatabaseHas('tests', ['created_at' => today()->subDays(59)]);

        $this->assertFalse(Storage::disk(config('purger.disk'))
            ->exists(sprintf('tests/%s.csv', today()->format('Y_m_d'))));

    }

    public function test_purge_old_records_and_write_to_file()
    {
        Config::set('app.purge.disk', 'local');
        Test::create(['created_at' => today()->subDays(59)]);
        Test::create(['created_at' => today()->subDays(64)]);
        Test::create(['created_at' => today()->subDays(66)]);

        PurgingPolicy::create([
            'table' => 'tests',
            'days_to_live' => 60,
            'write_to_file' => true
        ]);

        Storage::fake(config('app.purge.disk'));

        $this->artisan('utils:purge-logs')->assertExitCode(0)->run();
        $this->assertDatabaseCount('tests', 1);

        $this->assertDatabaseCount('test_archives', 0);

        $this->assertDatabaseHas('tests', ['created_at' => today()->subDays(59)]);

        $this->assertTrue(Storage::disk(config('purger.disk'))
            ->exists(sprintf('tests/%s.csv', today()->format('Y_m_d'))));

        $content = Storage::get(sprintf('tests/%s.csv', today()->format('Y_m_d')));
        $this->assertStringContainsString('2,Hello', $content);
        $this->assertStringContainsString('3,Hello', $content);
    }

    public function test_purge_old_records_and_write_to_archive_table()
    {
        Config::set('app.purge.disk', 'local');
        Test::create(['created_at' => today()->subDays(59)]);
        Test::create(['created_at' => today()->subDays(64)]);
        Test::create(['created_at' => today()->subDays(66)]);

        PurgingPolicy::create([
            'table' => 'tests',
            'days_to_live' => 60,
            'write_to_file' => false,
            'archive_table' => 'test_archives'
        ]);

        Storage::fake(config('app.purge.disk'));

        $this->artisan('utils:purge-logs')->assertExitCode(0)->run();
        $this->assertDatabaseCount('tests', 1);

        $this->assertDatabaseHas('tests', ['created_at' => today()->subDays(59)]);
        $this->assertDatabaseCount('test_archives', 2);

        $this->assertFalse(Storage::disk(config('purger.disk'))
            ->exists(sprintf('tests/%s.csv', today()->format('Y_m_d'))));
    }
}