<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ReportArtificialDeposit extends Eloquent
{
    protected $table = 'report_artificial_deposit';

    protected $fillable = [
        'report_l1_id',
        'account',
        'type',
        'amount',
        'order_no',
        'remark',
        'time',
    ];
}
