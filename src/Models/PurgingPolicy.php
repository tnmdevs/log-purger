<?php

namespace TNM\Utilities\LogPurger\Models;

use Illuminate\Database\Eloquent\Model;

class PurgingPolicy extends Model
{
    protected $guarded = [];
    protected $table = 'purging_policies';
}