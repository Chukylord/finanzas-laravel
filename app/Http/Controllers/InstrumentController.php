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

        $instruments = Instrument::query()
            ->when($q, function ($query) use ($q) {
                $query->where('symbol', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('market', 'like', "%{$q}%");
            })
            ->orderBy('active', 'desc')
            ->orderBy('type')
            ->orderBy('symbol')
            ->get();

        $watchIds = WatchlistItem::where('user_id', Auth::id())
            ->pluck('instrument_id')
            ->toArray();

        return view('instruments.index', compact('instruments', 'q', 'watchIds'));
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
        // Si está en watchlists, no lo borramos (para evitar problemas)
        $inUse = WatchlistItem::where('instrument_id', $instrument->id)->exists();
        if ($inUse) {
            return redirect()->route('instruments.index')
                ->with('error', 'No se puede eliminar: el instrumento está en una watchlist.');
        }

        $instrument->delete();
        return redirect()->route('instruments.index')->with('ok', 'Instrumento eliminado');
    }
}
