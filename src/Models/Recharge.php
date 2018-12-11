<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\RechargeLink;

class Recharge extends Eloquent
{
    protected $table = 'recharge';

    protected $primaryKey = 'id';

    protected $fillable = [
        'company_id',
        'platform_id',
        'pay_out_type',
        'pay_code',
        'amount',
        'order_no',
        'platform_order_no',
        'status',
        'remark',
        'recharge_link_id',
    ];

    public function rechargeLink()
    {
        return $this->belongsTo(RechargeLink::class, 'recharge_link_id');
    }
}
