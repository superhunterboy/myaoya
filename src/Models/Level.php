<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Level extends Eloquent
{
    protected $table = 'level';

    protected $fillable = [
        'id',
        'name',
        'status',
        'remark',
    ];

    protected $hidden = ['remark'];
}
