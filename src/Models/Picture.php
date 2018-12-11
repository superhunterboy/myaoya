<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Picture extends Eloquent
{
    protected $table = 'pictures';

    protected $primaryKey = 'id';

    protected $fillable = [
        'picture',
        'remark',
        'enabled',
        'type',
        'company_id',
    ];

    // protected $hidden = [];

    public $timestamps = true;
}
