<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\WatchlistItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function toggle(Instrument $instrument)
    {
        $item = WatchlistItem::where('user_id', Auth::id())
            ->where('instrument_id', $instrument->id)
            ->first();

        if ($item) {
            $item->delete();
            return back()->with('ok', 'Quitado de tu watchlist');
        }

        WatchlistItem::create([
            'user_id' => Auth::id(),
            'instrument_id' => $instrument->id,
            'horizon' => 'medium',
        ]);

        return back()->with('ok', 'Agregado a tu watchlist');
    }

    public function updateHorizon(Request $request, WatchlistItem $watchlistItem)
    {
        if ($watchlistItem->user_id != Auth::id()) abort(403);

        $request->validate([
            'horizon' => 'required|in:short,medium,long',
        ]);

        $watchlistItem->update([
            'horizon' => $request->horizon,
        ]);

        return back()->with('ok', 'Horizonte actualizado');
    }
}
