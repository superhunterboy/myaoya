<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Vendor extends Eloquent
{
    protected $table = 'vendor';

    protected $fillable = [
        'company_id',
        'pay_type',
        'no',
        'key',
        'callback_url',
        'notify_url',
        'error_count',
        'wechat',
        'wap_wechat',
        'alipay',
        'wap_alipay',
        'netpay',
        'qq',
        'wap_qq',
        'jd',
        'wap_jd',
        'baidu',
        'wap_baidu',
        'union',
        'wap_union',
    ];

    /**
     * 一个支付平台只属于一个业务平台
     */
    public function company()
    {
        return $this->belongsTo('Weiming\Models\Company');
    }
}
