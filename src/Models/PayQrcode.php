<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Qrcode;

class PayQrcode extends Eloquent
{
    protected $table = 'pay_qrcode';

    protected $fillable = [
        'sender',
        'money',
        'createTime',
        'code',
        'recType',
        'qrcode_id',
        'remark',
        'status',
        'result',
        'rk_user_id',
        'rk_user',
        'queue_job_id',
    ];

    public function qrcode()
    {
        return $this->belongsTo(Qrcode::class, 'qrcode_id');
    }
}
