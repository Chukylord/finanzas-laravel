<?php

namespace App\Http\Controllers;

use App\Models\WatchlistItem;
use App\Models\PriceSnapshot;
use App\Models\Signal;
use App\Services\MarketRecommendationService;
use Illuminate\Support\Facades\Auth;

class MarketController extends Controller
{
    public function index(MarketRecommendationService $recommendationService)
    {
        $watchlist = WatchlistItem::with('instrument')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        // último precio por instrumento (simple)
        $lastPrices = [];
        foreach ($watchlist as $w) {
            $last = PriceSnapshot::where('instrument_id', $w->instrument_id)
                ->orderBy('date', 'desc')
                ->first();

            $lastPrices[$w->instrument_id] = $last;
        }

        $marketData = $recommendationService->forWatchlist($watchlist);

        // últimas señales del usuario (si ya existen)
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
