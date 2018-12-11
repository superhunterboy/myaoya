<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/*
 * 银行卡管理
 */
class BankCards extends Eloquent
{
    protected $table = 'bank_cards';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bank_name',
        'user_name',
        'bank_number',
        'address',
        'type',
        'level_ids',
        'count',
        'money',
        'status',
    ];
}