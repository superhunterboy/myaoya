<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Qrcode;

class Company extends Eloquent
{
    protected $table = 'company';

    protected $fillable = [
        'no',
        'name',
        'url',
        'wechat_vendor_id',
        'wap_wechat_vendor_id',
        'alipay_vendor_id',
        'wap_alipay_vendor_id',
        'netbank_vendor_id',
        'qq_vendor_id',
        'wap_qq_vendor_id',
        'jd_vendor_id',
        'wap_jd_vendor_id',
        'baidu_vendor_id',
        'wap_baidu_vendor_id',
        'union_vendor_id',
        'wap_union_vendor_id',
        'autorecharge_url',
        'is_autorecharge',
        'is_5qrcode',
        'yun_vendor_id',
        'wap_yun_vendor_id'
    ];

    /**
     * 一个业务平台有多个支付平台
     */
    public function vendors()
    {
        return $this->hasMany('Weiming\Models\Vendor');
    }

    public function qrcodes()
    {
        return $this->hasMany(Qrcode::class, 'company_id');
    }
}
