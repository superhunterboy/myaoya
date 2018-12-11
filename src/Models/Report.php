<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weiming\Models\ReportL1;

class Report extends Eloquent
{
    protected $table = 'report';

    protected $fillable = [
        'name',
        'time',
        'flag',
        'total',
    ];

    public function reportL1s()
    {
        return $this->hasMany(ReportL1::class, 'report_id');
    }
}
