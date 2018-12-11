<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Permission extends Eloquent
{
    protected $table = 'permission';

    protected $fillable = [
        'name',
        'method',
        'route',
        'action',
        'category',
        'require',
    ];
}
