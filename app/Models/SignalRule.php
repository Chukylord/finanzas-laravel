<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalRule extends Model
{
    protected $fillable = [
        'user_id','name','horizon','fast_ma','slow_ma','active'
    ];
}
