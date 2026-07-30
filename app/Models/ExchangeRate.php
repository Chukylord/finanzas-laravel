<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'usd_ars',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'usd_ars' => 'decimal:4',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOnOrBefore(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('date', '<=', $date);
    }

    public static function latestForUserOnOrBefore(
        int $userId,
        CarbonInterface|string $date
    ): ?self {
        return self::query()
            ->forUser($userId)
            ->onOrBefore($date)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }
}
