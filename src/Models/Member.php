<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Level;

class Member extends Eloquent
{
    protected $table = 'member';

    protected $fillable = [
        'uid',
        'account',
        'level_id',
        'register_time',
        'status',
        'remark',
    ];

    protected $hidden = ['remark'];

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }
}
