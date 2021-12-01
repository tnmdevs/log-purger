<?php

namespace TNM\Utilities\LogPurger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArchiveTable extends Model
{
    protected $guarded = [];

    public static function ready(): Collection
    {
        return static::whereDate('last_run_on', '<', today())
            ->orWhereNull('last_run_on')
            ->get();
    }
}