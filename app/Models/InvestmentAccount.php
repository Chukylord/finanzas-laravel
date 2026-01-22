<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentAccount extends Model
{
    protected $fillable = [
        'user_id','name','default_currency','notes','active'
    ];

    public function movements()
    {
        return $this->hasMany(InvestmentMovement::class);
    }
}
