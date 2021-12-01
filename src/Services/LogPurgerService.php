<?php

namespace TNM\Utilities\LogPurger\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use TNM\Utilities\LogPurger\Models\ArchiveTable;

class LogPurgerService
{
    public function query(): int
    {
        ArchiveTable::ready()->each(function (ArchiveTable $table) {

            $date = DB::table($table->{'name'})
                ->whereDate('created_at', '<', today()->subDays($table->{'days_to_live'}))
                ->oldest()
                ->first(['created_at']);

            if (is_null($date)) {
                $table->update(['last_run_on' => today()->toDateString()]);
                return;
            }

            $date = Carbon::parse($date->created_at);

            $records = DB::table($table->{'name'})
                ->whereDate('created_at', $date->toDateString())
                ->get();

            $output = '';

            foreach ($records as $record) {
                $output .= implode(',', get_object_vars($record));
                $output .= "\r\n";
            }

            Storage::disk(config('purger.disk'))
                ->append(sprintf('%s%s%s.csv',
                    $table->{'name'},
                    DIRECTORY_SEPARATOR,
                    $date->format('Y_m_d')
                ), $output);

            DB::table($table->{'name'})
                ->whereDate('created_at', $date->toDateString())
                ->delete();

        });

        return 0;
    }
}