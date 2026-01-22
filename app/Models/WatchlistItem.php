<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{
    protected $fillable = ['user_id','instrument_id','horizon'];

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }
}
