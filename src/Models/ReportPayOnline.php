<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\ReportL1;

class ReportPayOnline extends Eloquent
{
    protected $table = 'report_pay_online';

    protected $fillable = [
        'report_l1_id',
        'order_no',
        'account',
        'currency',
        'level',
        'time',
        'amount',
    ];

    public function reportL1()
    {
        return $this->belongsTo(ReportL1::class, 'report_l1_id');
    }
}
