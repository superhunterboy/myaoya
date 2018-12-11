<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PayOut extends Eloquent
{
    protected $table = 'pay_out';

    protected $fillable = [
        'wid',
        'account',
        'realname',
        'mobile',
        'bank_card',
        'bank_name',
        'amount',
        'discount',
        'service_charge',
        'cash_info',
        'discount_deduction',
        'pay_out_status',
        'platform_id',
        'platform_type',
        'order_no',
        'platform_order_no',
        'status',
        'user_id',
        'user',
        'remark',
        'platform_attach',
        'crawl_attach',
        'pay_out_time',
        'pay_out_lastime',
        'job_id',
    ];

    protected $hidden = ['mobile', 'crawl_attach'];
}
