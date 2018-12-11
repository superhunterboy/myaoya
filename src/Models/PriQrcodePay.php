<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\User;
use Weiming\Models\PrivateQrcode;

/*
 * 个人二维码支付记录
 */
class PriQrcodePay extends Eloquent
{
    protected $table = 'pri_qrcode_pay';

    protected $primaryKey = 'id';

    protected $fillable = [
        'member',
        'money',
        'drawee',
        'qrcode_id',
        'status',
        'user_id',
        'type',
        'msg',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function qrcode()
    {
        return $this->belongsTo(PrivateQrcode::class, 'qrcode_id');
    }
}