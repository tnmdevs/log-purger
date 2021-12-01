<?php

namespace TNM\Utilities\LogPurger\Tests;

use Illuminate\Support\Facades\Storage;
use TNM\Utilities\LogPurger\Models\ArchiveTable;
use TNM\Utilities\LogPurger\Models\Test;

class LogPurgerServiceTest extends TestCase
{
    public function test_purge_old_records()
    {
        Test::create(['created_at' => today()->subDays(61)]);
        Test::create(['created_at' => today()->subDays(64)]);
        Test::create(['created_at' => today()->subDays(66)]);

        ArchiveTable::create([
            'name' => 'tests',
            'days_to_live' => 60
        ]);

        Storage::fake();

        $this->artisan('utils:purge-logs')->assertExitCode(0)->run();
        $this->assertDatabaseCount('tests', 2);

        $this->artisan('utils:purge-logs')->assertExitCode(0)->run();
        $this->assertDatabaseCount('tests', 1);
        $this->assertDatabaseHas('tests', ['created_at' => today()->subDays(61)]);

        $this->assertTrue(Storage::disk(config('purger.disk'))
            ->exists(sprintf('tests/%s.csv', today()->subDays(66)->format('Y_m_d'))));

        $this->assertFalse(Storage::disk(config('purger.disk'))
            ->exists(sprintf('tests/%s.csv', today()->subDays(61)->format('Y_m_d'))));
    }
}