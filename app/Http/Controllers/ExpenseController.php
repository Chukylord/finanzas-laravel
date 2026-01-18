<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) ($request->get('month', now()->month));
        $year  = (int) ($request->get('year', now()->year));

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $expenses = Expense::with('category')
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('expenses.index', compact('expenses', 'month', 'year'));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'date'        => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'method'      => 'nullable|string|max:50',
        ]);

        // Seguridad: categoría del usuario y tipo expense
        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        Expense::create([
            'user_id'     => Auth::id(),
            'category_id' => $request->category_id,
            'date'        => $request->date,
            'amount'      => $request->amount,
            'description' => $request->description,
            'method'      => $request->method ?? 'manual',
        ]);

        $d = Carbon::parse($request->date);
        return redirect()->route('expenses.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Egreso cargado');
    }

    public function edit(Expense $expense)
    {
        if ($expense->user_id != Auth::id()) abort(403);

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'date'        => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'method'      => 'nullable|string|max:50',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $expense->update([
            'category_id' => $request->category_id,
            'date'        => $request->date,
            'amount'      => $request->amount,
            'description' => $request->description,
            'method'      => $request->method ?? ($expense->method ?? 'manual'),
        ]);

        $d = Carbon::parse($request->date);
        return redirect()->route('expenses.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Egreso actualizado');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->user_id != Auth::id()) abort(403);

        $d = Carbon::parse($expense->date);
        $expense->delete();

        return redirect()->route('expenses.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Egreso eliminado');
    }
}
