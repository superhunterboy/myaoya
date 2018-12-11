<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\PayQrcode;
use Weiming\Models\Company;

class Qrcode extends Eloquent
{
    protected $table = 'qrcode';

    protected $fillable = [
        'wechat_id',
        'url',
        'limit',
        'money',
        'day_money',
        'count',
        'type',
        'company_id',
        'disable',
    ];

    public function payQrcodes()
    {
        return $this->hasMany(PayQrcode::class, 'qrcode_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
