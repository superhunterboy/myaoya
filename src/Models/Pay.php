<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Pay extends Eloquent
{
    protected $table = 'pay';

    protected $fillable = [
        'pay_type',
        'pay_code',
        'user',
        'device',
        'order_no',
        'vendor_order_no',
        'money',
        'rk_user_id',
        'rk_user',
        'rk_status',
        'status',
        'recharge_status',
        'recharge_count',
        'recharge_msg',
        'queue_job_id',
        'company_id',
        'vendor_id',
        'vendor_type',
        'pay_datetime',
    ];
}
