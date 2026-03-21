<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\WatchlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstrumentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $type = trim((string) $request->get('type', ''));
        $only = trim((string) $request->get('only', '')); // watchlist | not | ''
        $activeOnly = $request->boolean('active');

        $userId = Auth::id();

        // watchlist del user (map rápido para la vista)
        $watchIds = WatchlistItem::where('user_id', $userId)
            ->pluck('instrument_id')
            ->toArray();

        $watchMap = array_fill_keys($watchIds, true);

        $instrumentsQ = Instrument::query();

        if ($q !== '') {
            $instrumentsQ->where(function ($query) use ($q) {
                $query->where('symbol', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('market', 'like', "%{$q}%");
            });
        }

        if ($type !== '') {
            $instrumentsQ->where('type', $type);
        }

        if ($activeOnly) {
            $instrumentsQ->where('active', true);
        }

        // Filtro watchlist / no watchlist
        if ($only === 'watchlist') {
            $instrumentsQ->whereIn('id', $watchIds ?: [0]);
        } elseif ($only === 'not') {
            if (count($watchIds) > 0) {
                $instrumentsQ->whereNotIn('id', $watchIds);
            }
        }

        $instruments = $instrumentsQ
            ->orderBy('active', 'desc')
            ->orderBy('type')
            ->orderBy('symbol')
            ->paginate(50)
            ->withQueryString();

        return view('instruments.index', [
            'instruments' => $instruments,
            'q' => $q,
            'type' => $type,
            'only' => $only,
            'activeOnly' => $activeOnly,
            'watchIds' => $watchIds,
            'watchMap' => $watchMap,
        ]);
    }

    public function create()
    {
        return view('instruments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'symbol'   => 'required|string|max:20',
            'name'     => 'required|string|max:120',
            'type'     => 'required|in:accion,cedear,fci,crypto',
            'market'   => 'nullable|string|max:40',
            'currency' => 'required|in:ARS,USD',
            'active'   => 'nullable',
        ]);

        Instrument::create([
            'symbol'   => strtoupper(trim($request->symbol)),
            'name'     => trim($request->name),
            'type'     => $request->type,
            'market'   => $request->market ? strtoupper(trim($request->market)) : null,
            'currency' => $request->currency,
            'active'   => $request->has('active'),
        ]);

        return redirect()->route('instruments.index')->with('ok', 'Instrumento creado');
    }

    public function edit(Instrument $instrument)
    {
        return view('instruments.edit', compact('instrument'));
    }

    public function update(Request $request, Instrument $instrument)
    {
        $request->validate([
            'symbol'   => 'required|string|max:20',
            'name'     => 'required|string|max:120',
            'type'     => 'required|in:accion,cedear,fci,crypto',
            'market'   => 'nullable|string|max:40',
            'currency' => 'required|in:ARS,USD',
            'active'   => 'nullable',
        ]);

        $instrument->update([
            'symbol'   => strtoupper(trim($request->symbol)),
            'name'     => trim($request->name),
            'type'     => $request->type,
            'market'   => $request->market ? strtoupper(trim($request->market)) : null,
            'currency' => $request->currency,
            'active'   => $request->has('active'),
        ]);

        return redirect()->route('instruments.index')->with('ok', 'Instrumento actualizado');
    }

    public function destroy(Instrument $instrument)
    {
        $inUse = WatchlistItem::where('instrument_id', $instrument->id)->exists();
        if ($inUse) {
            return redirect()->route('instruments.index')
                ->with('error', 'No se puede eliminar: el instrumento está en una watchlist.');
        }

        $instrument->delete();
        return redirect()->route('instruments.index')->with('ok', 'Instrumento eliminado');
    }
}