<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/*
 * 个人二维码管理
 */
class PrivateQrcode extends Eloquent
{
    protected $table = 'private_qrcode';

    protected $primaryKey = 'id';

    protected $fillable = [
        'qrcode_name',
        'url',
        'money',
        'count',
        'type',
        'status',
        'msg',
    ];
}