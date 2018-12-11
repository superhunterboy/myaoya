<?php

namespace Weiming\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Affiche extends Eloquent
{
    protected $table = 'affiche';

    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'content',
        'status',
    ];
}
