<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    protected $fillable = [
        'user_id','instrument_id','signal_rule_id','date','action','score','reason'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }

    public function rule()
    {
        return $this->belongsTo(SignalRule::class, 'signal_rule_id');
    }
}
