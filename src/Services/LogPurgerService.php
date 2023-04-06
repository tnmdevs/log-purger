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
//        PurgingPolicy::ready()->each(function (PurgingPolicy $table) {
//
//            $date = DB::table($table->{'name'})
//                ->whereDate('created_at', '<', today()->subDays($table->{'days_to_live'}))
//                ->oldest()
//                ->first(['created_at']);
//
//            if (is_null($date)) {
//                $table->update(['last_run_on' => today()->toDateString()]);
//                return;
//            }
//
//            $date = Carbon::parse($date->created_at);
//
//            $records = DB::table($table->{'name'})
//                ->whereDate('created_at', $date->toDateString())
//                ->get();
//
//            $output = '';
//
//            foreach ($records as $record) {
//                $output .= implode(',', get_object_vars($record));
//                $output .= "\r\n";
//            }
//
//            Storage::disk(config('purger.disk'))
//                ->append(sprintf('%s%s%s.csv',
//                    $table->{'name'},
//                    DIRECTORY_SEPARATOR,
//                    $date->format('Y_m_d')
//                ), $output);
//
//            DB::table($table->{'name'})
//                ->whereDate('created_at', $date->toDateString())
//                ->delete();
//
//        });

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