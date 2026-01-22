<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    protected $fillable = [
        'symbol','name','type','market','currency','active'
    ];

    public function snapshots()
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
