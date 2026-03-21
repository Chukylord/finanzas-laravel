<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSnapshot extends Model
{
    protected $fillable = [
        'instrument_id','date','close','open','high','low','volume'
    ];

    protected $casts = [
        'date' => 'date',
        'fetched_at' => 'datetime',
    ];

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }
}
