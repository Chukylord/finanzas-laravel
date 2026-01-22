<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentMovement extends Model
{
    protected $fillable = [
        'investment_account_id','date','type','currency','amount','description'
    ];

    public function account()
    {
        return $this->belongsTo(InvestmentAccount::class, 'investment_account_id');
    }

    // helper: signo según type
    public function signedAmount(): float
    {
        $amt = (float) $this->amount;

        return in_array($this->type, ['withdraw','loss','fee'])
            ? -$amt
            : $amt;
    }
}
