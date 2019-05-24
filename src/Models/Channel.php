<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Channel extends Eloquent
{

    protected $fillable = [
        'name',
        'tag',
        'position',
        'status',
        'sequence',
    ];

}
