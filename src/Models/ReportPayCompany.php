<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ReportPayCompany extends Eloquent
{
    protected $table = 'report_pay_company';

    protected $fillable = [
        'report_l1_id',
        'level',
        'order_no',
        'shareholder',
        'account',
        'account_bank',
        'depositor',
        'way',
        'amount',
        'discount',
        'other_discount1',
        'other_discount2',
        'total_amount',
        'company_bank',
        'company_bank_user',
        'operator',
        'member_datetime',
        'system_datetime',
        'operation_datetime',
    ];
}
