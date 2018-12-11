<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PayOutLimit extends Eloquent
{
    protected $table = 'pay_out_limit';

    protected $fillable = [
        'level_ids',
        'count',
    ];
}
