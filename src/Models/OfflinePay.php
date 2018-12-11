<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\User;
use Weiming\Models\BankCards;

class OfflinePay extends Eloquent
{
    protected $table = 'offline_pay';

    protected $primaryKey = 'id';

    protected $fillable = [
        'order_no',
        'account',
        'amount',
        'depositor',
        'bank_name',
        'bank_card_no',
        'type',
        'card_id',
        'card_user',
        'status',
        'remark',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankCard()
    {
        return $this->belongsTo(BankCards::class, 'card_id');
    }
}
