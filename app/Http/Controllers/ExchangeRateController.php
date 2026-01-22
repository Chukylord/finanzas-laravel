<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    public function index()
    {

        $today = Carbon::today()->toDateString();

        $todayRate = ExchangeRate::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        $rates = ExchangeRate::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();

        return view('exchange_rates.index', compact('rates', 'todayRate', 'today'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'usd_ars' => 'required|numeric|min:0',
        ]);

        // Un registro por usuario + fecha (si ya existe, lo actualiza)
        ExchangeRate::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'date' => $request->date,
            ],
            [
                'usd_ars' => $request->usd_ars,
            ]
        );

        return redirect()->route('exchange-rates.index')->with('ok', 'Tipo de cambio guardado');
    }

    public function destroy(ExchangeRate $exchange_rate)
    {
        if ($exchange_rate->user_id != Auth::id()) abort(403);

        $exchange_rate->delete();

        return redirect()->route('exchange-rates.index')->with('ok', 'Tipo de cambio eliminado');
    }
}
