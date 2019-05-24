<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Lock extends Eloquent
{

    protected $fillable = [
        'id',
        'order_no',
        'created_at',
    ];
}
