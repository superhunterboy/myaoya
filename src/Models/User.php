<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class User extends Eloquent
{
    protected $table = 'user';

    protected $fillable = [
        'username',
        'password',
        'realname',
        'type',
        'permissions',
        'company_ids',
        'lastlogin',
        'ip',
        'status',
        'secret',
        'is_bind',
    ];

    protected $hidden = ['password', 'secret'];
}
