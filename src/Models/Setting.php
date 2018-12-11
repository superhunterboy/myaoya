<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Setting extends Eloquent
{
    protected $table = 'setting';

    protected $primaryKey = 'id';

    protected $fillable = [
        'key',
        'val',
    ];
}
