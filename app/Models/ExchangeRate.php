<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'usd_ars',
    ];

    protected $casts = [
        'date' => 'date',
        'usd_ars' => 'decimal:2',
    ];
}
