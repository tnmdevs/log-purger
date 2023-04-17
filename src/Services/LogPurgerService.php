<?php

namespace TNM\Utilities\LogPurger\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use TNM\Utilities\LogPurger\Models\PurgingPolicy;

class LogPurgerService
{
    private int $limit;

    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }

    public function query(): int
    {
        PurgingPolicy::whereDate('last_purged_on', '<', today())->orWhereNull('last_purged_on')->get()
            ->each(function (PurgingPolicy $policy) {
                $query = DB::table($policy->{'table'})
                    ->whereDate('created_at', '<', today()->subDays($policy->{'days_to_live'}))
                    ->limit($this->limit);

                if ($policy->{'write_to_file'} || $policy->{'archive_table'}) {
                    $query->get()->each(function ($record) use ($policy) {
                        if ($policy->{'write_to_file'}) {
                            $this->writeToFile($policy, $record);
                        }
                        if ($policy->{'archive_table'}) {
                            $this->writeToArchiveTable($policy, $record);
                        }
                    });
                }

                $query->delete();

                $hasNoRemainder = DB::table($policy->{'table'})
                    ->whereDate('created_at', '<', today()->subDays($policy->{'days_to_live'}))
                    ->doesntExist();

                if ($hasNoRemainder) $policy->update(['last_purged_on' => today()]);
            });
        return 0;
    }

    protected function writeToFile(PurgingPolicy $policy, $record): void
    {
        Storage::disk(config('purger.disk'))
            ->append(
                sprintf('%s%s%s.csv',
                    $policy->{'table'},
                    DIRECTORY_SEPARATOR,
                    today()->format('Y_m_d')), implode(',', get_object_vars($record)));
    }

    protected function writeToArchiveTable(PurgingPolicy $policy, $record): void
    {
        DB::table($policy->{'archive_table'})->insert(
            [
                ...collect((get_object_vars($record)))->except(['id', 'created_at', 'updated_at'])->toArray(),
                'archived_at' => now(),
                'original_id' => $record->{'id'}
            ]
        );
    }

}