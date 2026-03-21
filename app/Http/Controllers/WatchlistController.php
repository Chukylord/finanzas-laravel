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

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:add,remove',
            'instrument_ids' => 'required|array|min:1',
            'instrument_ids.*' => 'integer',
            'horizon' => 'nullable|in:short,medium,long',
        ]);

        $userId = Auth::id();
        $ids = array_unique($data['instrument_ids']);
        $action = $data['action'];
        $horizon = $data['horizon'] ?? 'medium';

        if ($action === 'add') {
            $rows = [];
            $now = now();

            foreach ($ids as $id) {
                $rows[] = [
                    'user_id' => $userId,
                    'instrument_id' => $id,
                    'horizon' => $horizon,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // evita duplicados
            WatchlistItem::upsert(
                $rows,
                ['user_id', 'instrument_id'],
                ['horizon', 'updated_at']
            );

            return back()->with('ok', 'Agregados a tu watchlist: ' . count($ids));
        }

        // remove
        WatchlistItem::where('user_id', $userId)
            ->whereIn('instrument_id', $ids)
            ->delete();

        return back()->with('ok', 'Quitados de tu watchlist: ' . count($ids));
    }
}
