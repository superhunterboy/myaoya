<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\User;
use Weiming\Models\Merchant;

/*
 * 商家二维码支付记录
 */
class MerQrcodePay extends Eloquent
{
    protected $table = 'mer_qrcode_pay';

    protected $primaryKey = 'id';

    protected $fillable = [
        'member',
        'recharge_money',
        'order',
        'merchant_id',
        'type',
        'original_money',
        'discount',
        'money',
        'hand_charge',
        'status',
        'user_id',
        'msg',
        'pay_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}