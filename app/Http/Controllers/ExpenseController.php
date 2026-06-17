<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $categoryId = $request->get('category_id');
        $subcategoryId = $request->get('subcategory_id');

        $categories = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', $userId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense');
            })
            ->orderBy('name')
            ->get();

        $expenses = Expense::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($subcategoryId, function ($query) use ($subcategoryId) {
                $query->where('subcategory_id', $subcategoryId);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalExpense = $expenses->sum('amount');

        return view('expenses.index', compact(
            'expenses',
            'categories',
            'subcategories',
            'month',
            'year',
            'categoryId',
            'subcategoryId',
            'totalExpense'
        ));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense');
            })
            ->orderBy('name')
            ->get();

        return view('expenses.create', compact('categories', 'subcategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'date'           => 'required|date',
            'amount'         => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:255',
            'method'         => 'nullable|string|max:50',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        if ($request->subcategory_id) {
            $subOk = Subcategory::where('id', $request->subcategory_id)
                ->where('user_id', Auth::id())
                ->where('category_id', $request->category_id)
                ->exists();

            if (!$subOk) {
                return back()->withErrors(['subcategory_id' => 'Subcategoría inválida para esa categoría'])->withInput();
            }
        }

        Expense::create([
            'user_id'        => Auth::id(),
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'date'           => $request->date,
            'amount'         => $request->amount,
            'description'    => $request->description,
            'method'         => $request->method ?? 'manual',
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

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense');
            })
            ->orderBy('name')
            ->get();

        return view('expenses.edit', compact('expense', 'categories', 'subcategories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'date'           => 'required|date',
            'amount'         => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:255',
            'method'         => 'nullable|string|max:50',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        if ($request->subcategory_id) {
            $subOk = Subcategory::where('id', $request->subcategory_id)
                ->where('user_id', Auth::id())
                ->where('category_id', $request->category_id)
                ->exists();

            if (!$subOk) {
                return back()->withErrors(['subcategory_id' => 'Subcategoría inválida para esa categoría'])->withInput();
            }
        }

        $expense->update([
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'date'           => $request->date,
            'amount'         => $request->amount,
            'description'    => $request->description,
            'method'         => $request->method ?? ($expense->method ?? 'manual'),
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