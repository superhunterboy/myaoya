<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PaymentChannel extends Eloquent
{

    protected $fillable = [
        'platform',
        'platform_identifer',
        'channel',
        'paycode',
        'merchant_no',
        'key',
        'display_name',
        'position',
        'offline_category',
        'deposit_range',
        'callback_url',
        'notify_url',
        'status',
        'remark',
        'sequence',
    ];

}
