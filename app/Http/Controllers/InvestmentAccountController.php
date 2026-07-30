<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\InvestmentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentAccountController extends Controller
{
    public function index()
    {
        $accounts = InvestmentAccount::with('movements')
            ->where('user_id', Auth::id())
            ->orderBy('active', 'desc')
            ->orderBy('name')
            ->get();

        // Cotización propia más reciente, sin usar fechas futuras.
        $rate = ExchangeRate::latestForUserOnOrBefore((int) Auth::id(), now());
        $usdArs = $rate?->usd_ars;

        // Saldos por cuenta (ARS y USD separados)
        $balances = [];
        foreach ($accounts as $a) {
            $ars = 0.0;
            $usd = 0.0;

            foreach ($a->movements as $m) {
                $signed = in_array($m->type, ['withdraw', 'loss', 'fee']) ? -(float) $m->amount : (float) $m->amount;
                if ($m->currency === 'USD') {
                    $usd += $signed;
                } else {
                    $ars += $signed;
                }
            }

            $balances[$a->id] = [
                'ARS' => $ars,
                'USD' => $usd,
                'ARS_equiv' => abs($usd) < 0.00001
                    ? $ars
                    : ($usdArs !== null ? ($ars + $usd * (float) $usdArs) : null),
            ];
        }

        return view('investments.index', compact('accounts', 'balances', 'usdArs', 'rate'));
    }

    public function create()
    {
        return view('investments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'default_currency' => 'required|in:ARS,USD',
            'notes' => 'nullable|string|max:1000',
        ]);

        InvestmentAccount::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'default_currency' => $request->default_currency,
            'notes' => $request->notes,
            'active' => true,
        ]);

        return redirect()->route('investments.index')->with('ok', 'Cuenta de inversión creada');
    }

    public function edit(InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) {
            abort(403);
        }

        return view('investments.edit', ['account' => $investment]);
    }

    public function update(Request $request, InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'default_currency' => 'required|in:ARS,USD',
            'notes' => 'nullable|string|max:1000',
            'active' => 'nullable',
        ]);

        $investment->update([
            'name' => $request->name,
            'default_currency' => $request->default_currency,
            'notes' => $request->notes,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('investments.index')->with('ok', 'Cuenta actualizada');
    }

    public function destroy(InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) {
            abort(403);
        }

        $investment->delete();

        return redirect()->route('investments.index')->with('ok', 'Cuenta eliminada');
    }
}
