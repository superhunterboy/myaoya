<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\Report;
use Weiming\Models\ReportPayOnline;

class ReportL1 extends Eloquent
{
    protected $table = 'report_l1';

    protected $fillable = [
        'report_id',
        'text',
        'total',
        'tag',
        'currency',
        'total_user',
        'total_amount',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function ReportPayOnlines()
    {
        return $this->hasMany(ReportPayOnline::class, 'report_l1_id');
    }
}
