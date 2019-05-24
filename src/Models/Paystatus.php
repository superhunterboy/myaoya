<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Paystatus extends Eloquent
{
    protected $table = 'Paystatus';

    protected $fillable = [
        'id',
        'keys',
        'payname',
        'status',
    ];
}
