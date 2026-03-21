<?php

namespace App\Services;

use App\Models\PriceSnapshot;
use Carbon\Carbon;

class SignalEngineService
{
    public function sma(array $values, int $period): ?float
    {
        if (count($values) < $period) return null;
        $slice = array_slice($values, -$period);
        return array_sum($slice) / $period;
    }

    public function ema(array $values, int $period): ?float
    {
        if (count($values) < $period) return null;

        $k = 2 / ($period + 1);
        $ema = $values[0];

        foreach ($values as $v) {
            $ema = ($v * $k) + ($ema * (1 - $k));
        }

        return $ema;
    }

    public function rsi(array $values, int $period = 14): ?float
    {
        if (count($values) < ($period + 1)) return null;

        $gains = 0.0;
        $losses = 0.0;

        $start = count($values) - ($period + 1);
        for ($i = $start + 1; $i < count($values); $i++) {
            $diff = $values[$i] - $values[$i - 1];
            if ($diff >= 0) $gains += $diff;
            else $losses += abs($diff);
        }

        if ($losses == 0) return 100.0;

        $rs = ($gains / $period) / ($losses / $period);
        return 100 - (100 / (1 + $rs));
    }

    /**
     * Señal simple:
     * - BUY: fast MA cruza arriba slow MA + RSI no sobrecomprado
     * - SELL: fast MA cruza abajo slow MA o RSI sobrecomprado
     * - HOLD: resto
     */
    public function evaluate(array $closes, int $fast, int $slow, string $mode = 'sma'): array
    {
        $need = max($slow + 5, 60);
        if (count($closes) < $need) {
            return ['action' => 'hold', 'score' => 0, 'reason' => 'Datos insuficientes'];
        }

        // actuales
        $fastNow = ($mode === 'ema') ? $this->ema($closes, $fast) : $this->sma($closes, $fast);
        $slowNow = ($mode === 'ema') ? $this->ema($closes, $slow) : $this->sma($closes, $slow);

        // anteriores (para detectar cruce)
        $prev = array_slice($closes, 0, -1);
        $fastPrev = ($mode === 'ema') ? $this->ema($prev, $fast) : $this->sma($prev, $fast);
        $slowPrev = ($mode === 'ema') ? $this->ema($prev, $slow) : $this->sma($prev, $slow);

        $rsi = $this->rsi($closes, 14);

        if ($fastNow === null || $slowNow === null || $fastPrev === null || $slowPrev === null || $rsi === null) {
            return ['action' => 'hold', 'score' => 0, 'reason' => 'Datos insuficientes'];
        }

        $crossUp   = ($fastPrev <= $slowPrev) && ($fastNow > $slowNow);
        $crossDown = ($fastPrev >= $slowPrev) && ($fastNow < $slowNow);

        $action = 'hold';
        $reason = '';
        $score  = 50;

        if ($crossUp && $rsi < 70) {
            $action = 'buy';
            $reason = "Cruce alcista {$mode}({$fast}/{$slow}) + RSI " . number_format($rsi, 1);
            $score  = 70 + min(30, (int) ((70 - $rsi) / 2));
        } elseif ($crossDown || $rsi > 75) {
            $action = 'sell';
            $reason = ($crossDown ? "Cruce bajista {$mode}({$fast}/{$slow})" : "RSI alto " . number_format($rsi, 1));
            $score  = 70 + min(30, (int) (($rsi - 70) * 2));
        } else {
            $action = 'hold';
            $reason = "{$mode}({$fast}/{$slow}) estable + RSI " . number_format($rsi, 1);
            $score  = 50;
        }

        return compact('action','score','reason');
    }

    /**
     * Devuelve closes ordenados por fecha asc.
     */
    public function closesForInstrument(int $instrumentId, int $limit = 300): array
    {
        return PriceSnapshot::where('instrument_id', $instrumentId)
            ->orderBy('date', 'asc')
            ->limit($limit)
            ->pluck('close')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }
}
