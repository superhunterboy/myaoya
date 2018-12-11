<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Retry extends Eloquent
{
    protected $table = 'retry';

    protected $primaryKey = 'id';

    protected $fillable = [
        'order_no',
        'count',
    ];

    // protected $hidden = [];

    public $timestamps = true;
}
