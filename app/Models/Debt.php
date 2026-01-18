<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Category;

class Debt extends Model
{
    protected $fillable = [
        'user_id','category_id','name','type',
        'total_amount','installments_total','installments_paid',
        'installment_amount','day_of_month','next_due_date','active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function remainingInstallments()
    {
        $rem = $this->installments_total - $this->installments_paid;
        return $rem < 0 ? 0 : $rem;
    }
}
