<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Code extends Eloquent
{
    protected $table = 'codes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'mobile',
        'code',
        'send_id',
        'status',
        'ok',
    ];

    // protected $hidden = [''];

    public $timestamps = true;
}
