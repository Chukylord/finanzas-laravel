<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Recurring;
use App\Models\Debt;

class Category extends Model
{
    protected $fillable = ['user_id', 'name', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function recurrings()
    {
        return $this->hasMany(Recurring::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
