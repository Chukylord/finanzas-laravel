<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) ($request->get('month', now()->month));
        $year  = (int) ($request->get('year', now()->year));

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $incomes = Income::with('category')
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('incomes.index', compact('incomes', 'month', 'year'));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'income')
            ->orderBy('name')
            ->get();

        return view('incomes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:255',
        ]);

        // Seguridad: categoría del usuario y tipo income
        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'income')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        Income::create([
            'user_id'      => Auth::id(),
            'category_id'  => $request->category_id,
            'date'         => $request->date,
            'amount'       => $request->amount,
            'description'  => $request->description,
        ]);

        // volver al mes del ingreso cargado
        $d = Carbon::parse($request->date);
        return redirect()->route('incomes.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Ingreso cargado');
    }

    public function edit(Income $income)
    {
        if ($income->user_id != Auth::id()) abort(403);

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'income')
            ->orderBy('name')
            ->get();

        return view('incomes.edit', compact('income', 'categories'));
    }

    public function update(Request $request, Income $income)
    {
        if ($income->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'date'        => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'income')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $income->update([
            'category_id' => $request->category_id,
            'date'        => $request->date,
            'amount'      => $request->amount,
            'description' => $request->description,
        ]);

        $d = Carbon::parse($request->date);
        return redirect()->route('incomes.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Ingreso actualizado');
    }

    public function destroy(Income $income)
    {
        if ($income->user_id != Auth::id()) abort(403);

        $d = Carbon::parse($income->date);
        $income->delete();

        return redirect()->route('incomes.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Ingreso eliminado');
    }
}
