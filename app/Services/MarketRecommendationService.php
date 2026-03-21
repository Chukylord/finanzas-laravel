<?php

namespace App\Services;

use App\Models\PriceSnapshot;
use App\Models\WatchlistItem;
use Illuminate\Support\Collection;

class MarketRecommendationService
{
    public function __construct(private IolClient $iol)
    {
    }

    public function forWatchlist(Collection $watchlist): array
    {
        $recommendations = [];
        $warnings = [];

        foreach ($watchlist as $item) {
            /** @var WatchlistItem $item */
            $instrument = $item->instrument;
            if (!$instrument) {
                continue;
            }

            $symbol = $instrument->symbol;
            $horizon = $item->horizon;
            $market = $instrument->market ?: 'bCBA';

            $snapshots = PriceSnapshot::where('instrument_id', $instrument->id)
                ->orderByDesc('date')
                ->limit(30)
                ->get(['date', 'close']);

            $lastSnapshot = $snapshots->first();
            $previousSnapshot = $snapshots->skip(1)->first();

            try {
                $quote = $this->iol->getQuote($market, $symbol);
            } catch (\Throwable $e) {
                $quote = [];
                $warnings[] = "No se pudo consultar IOL para {$symbol}: {$e->getMessage()}";
            }

            $currentPrice = (float) (
                data_get($quote, 'ultimoPrecio')
                ?? data_get($quote, 'ultimo')
                ?? $lastSnapshot?->close
                ?? 0
            );

            if ($currentPrice <= 0) {
                continue;
            }

            $changePct = data_get($quote, 'variacionPorcentual');
            if ($changePct === null && $lastSnapshot && $previousSnapshot && (float) $previousSnapshot->close > 0) {
                $changePct = (($lastSnapshot->close - $previousSnapshot->close) / $previousSnapshot->close) * 100;
            }
            $changePct = (float) ($changePct ?? 0);

            $closes = $snapshots->pluck('close')->reverse()->map(fn ($value) => (float) $value)->values();
            if ($closes->isEmpty() || (float) $closes->last() !== $currentPrice) {
                $closes->push($currentPrice);
            }

            $sma5 = $this->sma($closes, 5);
            $sma20 = $this->sma($closes, 20);

            [$action, $score, $reason] = $this->decide($horizon, $currentPrice, $changePct, $sma5, $sma20);

            $recommendations[] = [
                'instrument' => $instrument,
                'horizon' => $horizon,
                'action' => $action,
                'score' => $score,
                'reason' => $reason,
                'price' => $currentPrice,
                'change_pct' => round($changePct, 2),
                'source' => !empty($quote) ? 'IOL' : 'Histórico local',
            ];
        }

        usort($recommendations, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return [
            'recommendations' => $recommendations,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function sma(Collection $closes, int $period): ?float
    {
        if ($closes->count() < $period) {
            return null;
        }

        return round($closes->take(-$period)->avg(), 4);
    }

    private function decide(string $horizon, float $price, float $changePct, ?float $sma5, ?float $sma20): array
    {
        if ($horizon === 'short') {
            if ($changePct >= 1 && (!$sma5 || $price >= $sma5)) {
                return ['buy', min(95, (int) (62 + abs($changePct) * 9)), 'Momentum positivo de corto plazo'];
            }

            if ($changePct <= -1 && (!$sma5 || $price <= $sma5)) {
                return ['sell', min(95, (int) (62 + abs($changePct) * 9)), 'Debilidad de corto plazo'];
            }

            return ['hold', 50, 'Sin señal clara en corto plazo'];
        }

        if ($horizon === 'long') {
            if ($sma20 && $price > $sma20 * 1.02) {
                return ['buy', min(92, (int) (58 + (($price / $sma20) - 1) * 500)), 'Tendencia por encima de la media de 20 ruedas'];
            }

            if ($sma20 && $price < $sma20 * 0.98) {
                return ['sell', min(92, (int) (58 + (1 - ($price / $sma20)) * 500)), 'Precio debajo de la media de 20 ruedas'];
            }

            return ['hold', 52, 'Esperar confirmación de tendencia de largo plazo'];
        }

        if ($sma5 && $sma20 && $sma5 > $sma20 && $changePct > 0.4) {
            return ['buy', 60, 'Sesgo alcista moderado'];
        }

        if ($sma5 && $sma20 && $sma5 < $sma20 && $changePct < -0.4) {
            return ['sell', 60, 'Sesgo bajista moderado'];
        }

        return ['hold', 50, 'Horizonte medio sin señal dominante'];
    }
}