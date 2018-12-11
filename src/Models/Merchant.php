<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/*
 * 商家管理
 */
class Merchant extends Eloquent
{
    protected $table = 'merchant';

    protected $primaryKey = 'id';

    protected $fillable = [
        'open_id',
        'open_key',
        'shop_no',
        'merchant_name',
        'signboard_name',
        'address',
        'status',
        'type',
        'key',
    ];
}