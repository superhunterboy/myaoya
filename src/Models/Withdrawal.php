<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Platform;
use Weiming\Models\User;

class Withdrawal extends Eloquent
{
    protected $table = 'withdrawal';

    protected $primaryKey = 'id';

    protected $fillable = [
        'platform_id',
        'order_no',
        'platform_order_no',
        'member_id',
        'account',
        'bank_no',
        'bank_name',
        'username',
        'amount',
        'mobile',
        'province',
        'city',
        'branch',
        'status',
        'remark',
        'user_id',
        'note',
        'job_id',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
