<?php

namespace App\Http\Controllers;

use App\Models\InvestmentAccount;
use App\Models\InvestmentMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentMovementController extends Controller
{
    public function index(InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) abort(403);

        $movements = $investment->movements()
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('investments.movements.index', compact('investment', 'movements'));
    }

    public function create(InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) abort(403);

        return view('investments.movements.create', compact('investment'));
    }

    public function store(Request $request, InvestmentAccount $investment)
    {
        if ($investment->user_id != Auth::id()) abort(403);

        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:deposit,withdraw,profit,loss,fee,adjust',
            'currency' => 'required|in:ARS,USD',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        InvestmentMovement::create([
            'investment_account_id' => $investment->id,
            'date' => $request->date,
            'type' => $request->type,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        return redirect()->route('investments.movements.index', $investment)->with('ok', 'Movimiento agregado');
    }

    public function destroy(InvestmentMovement $movement)
    {
        // seguridad: verificar dueño por relación
        $account = $movement->account;
        if (!$account || $account->user_id != Auth::id()) abort(403);

        $movement->delete();

        return redirect()->route('investments.movements.index', $account)->with('ok', 'Movimiento eliminado');
    }
}
