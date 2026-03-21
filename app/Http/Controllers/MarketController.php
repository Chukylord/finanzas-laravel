<?php

namespace App\Http\Controllers;

use App\Models\WatchlistItem;
use App\Models\PriceSnapshot;
use App\Models\Signal;
use App\Services\MarketRecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    public function index(MarketRecommendationService $recommendationService)
    {
        $watchlist = WatchlistItem::with('instrument')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // IDs de instrumentos en watchlist
        $instrumentIds = $watchlist->pluck('instrument_id')->filter()->unique()->values();

        // ✅ Último snapshot por instrumento (en UNA consulta)
        $lastPrices = [];
        if ($instrumentIds->isNotEmpty()) {

            // Subquery: max(date) por instrument_id
            $sub = PriceSnapshot::select('instrument_id', DB::raw('MAX(date) as max_date'))
                ->whereIn('instrument_id', $instrumentIds)
                ->groupBy('instrument_id');

            // Join para traer la fila completa del último día
            $latest = PriceSnapshot::joinSub($sub, 't', function ($join) {
                    $join->on('price_snapshots.instrument_id', '=', 't.instrument_id')
                         ->on('price_snapshots.date', '=', 't.max_date');
                })
                ->select('price_snapshots.*')
                ->get();

            foreach ($latest as $snap) {
                $lastPrices[$snap->instrument_id] = $snap;
            }
        }

        // Recomendaciones (por ahora: sobre watchlist)
        $marketData = $recommendationService->forWatchlist($watchlist);

        // Últimas señales del usuario
        $signals = Signal::with('instrument')
            ->where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('markets.index', [
            'watchlist' => $watchlist,
            'lastPrices' => $lastPrices,
            'signals' => $signals,
            'recommendations' => $marketData['recommendations'],
            'marketWarnings' => $marketData['warnings'],
        ]);
    }
}