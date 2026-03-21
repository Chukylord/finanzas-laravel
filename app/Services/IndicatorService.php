<?php

namespace App\Services;

class IndicatorService
{
    // SMA simple
    public function sma(array $closes, int $period): ?float
    {
        if ($period <= 0) return null;
        if (count($closes) < $period) return null;

        $slice = array_slice($closes, -$period);
        return array_sum($slice) / $period;
    }

    // EMA
    public function ema(array $closes, int $period): ?float
    {
        if ($period <= 0) return null;
        if (count($closes) < $period) return null;

        $k = 2 / ($period + 1);

        // arrancamos con SMA del primer periodo disponible
        $startSlice = array_slice($closes, 0, $period);
        $ema = array_sum($startSlice) / $period;

        for ($i = $period; $i < count($closes); $i++) {
            $price = $closes[$i];
            $ema = ($price * $k) + ($ema * (1 - $k));
        }

        return $ema;
    }

    // RSI (Wilder)
    public function rsi(array $closes, int $period = 14): ?float
    {
        if ($period <= 0) return null;
        if (count($closes) < $period + 1) return null;

        $gains = 0.0;
        $losses = 0.0;

        // primer promedio
        for ($i = 1; $i <= $period; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff >= 0) $gains += $diff;
            else $losses += abs($diff);
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        // suavizado
        for ($i = $period + 1; $i < count($closes); $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            $gain = $diff > 0 ? $diff : 0;
            $loss = $diff < 0 ? abs($diff) : 0;

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
        }

        if ($avgLoss == 0) return 100.0;

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }
}
