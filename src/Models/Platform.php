<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\RechargeLink;

class Platform extends Eloquent
{
    protected $table = 'platform';

    protected $primaryKey = 'id';

    protected $fillable = [
        'company_id',
        'pay_out_type',
        'no',
        'key',
        'start_amount_limit',
        'end_amount_limit',
        'balance',
        'enabled',
        'callback_url',
        'notify_url',
        'type',
    ];

    public function rechargeLinks()
    {
        return $this->hasMany(RechargeLink::class, 'platform_id');
    }
}
