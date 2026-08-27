<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveReport extends Model
{
    protected $fillable = [
        'type',
        'description',
        'latitude',
        'longitude',
        'reporter_name'
    ];
}
