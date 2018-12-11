<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Platform;

class RechargeLink extends Eloquent
{
    protected $table = 'recharge_link';

    protected $primaryKey = 'id';

    protected $fillable = [
        'platform_id',
        'token',
        'remark',
        'status',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }
}
