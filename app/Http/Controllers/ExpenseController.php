<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'integer',
            'bulk_category_id' => 'nullable|integer',
            'bulk_subcategory_id' => 'nullable|integer',
            'clear_subcategory' => 'nullable|boolean',
        ]);

        $userId = Auth::id();
        $ids = collect($request->input('selected_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $categoryId = $request->filled('bulk_category_id') ? (int) $request->bulk_category_id : null;
        $clearSubcategory = $request->boolean('clear_subcategory');
        $subcategoryId = (! $clearSubcategory && $request->filled('bulk_subcategory_id'))
            ? (int) $request->bulk_subcategory_id
            : null;

        if (! $categoryId && ! $subcategoryId && ! $clearSubcategory) {
            return back()
                ->withErrors(['bulk_action' => 'Elegí una categoría, una subcategoría o limpiar subcategoría.'])
                ->withInput();
        }

        if ($subcategoryId && ! $categoryId) {
            return back()
                ->withErrors(['bulk_subcategory_id' => 'Para asignar una subcategoría, elegí también su categoría.'])
                ->withInput();
        }

        if ($categoryId) {
            $category = Category::where('user_id', $userId)
                ->where('type', 'expense')
                ->find($categoryId);

            if (! $category) {
                return back()
                    ->withErrors(['bulk_category_id' => 'La categoría seleccionada no es válida para egresos.'])
                    ->withInput();
            }
        }

        if ($subcategoryId) {
            $subcategory = Subcategory::where('user_id', $userId)
                ->where('category_id', $categoryId)
                ->find($subcategoryId);

            if (! $subcategory) {
                return back()
                    ->withErrors(['bulk_subcategory_id' => 'La subcategoría no pertenece a la categoría seleccionada.'])
                    ->withInput();
            }
        }

        $expenses = Expense::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get();

        if ($expenses->count() !== $ids->count()) {
            return back()
                ->withErrors(['selected_ids' => 'Algunos egresos seleccionados no existen o no pertenecen a tu usuario.'])
                ->withInput();
        }

        $validSubcategoryIds = $categoryId
            ? Subcategory::where('user_id', $userId)->where('category_id', $categoryId)->pluck('id')
            : collect();

        DB::transaction(function () use ($expenses, $categoryId, $clearSubcategory, $subcategoryId, $validSubcategoryIds): void {
            foreach ($expenses as $expense) {
                if ($categoryId) {
                    $expense->category_id = $categoryId;
                }

                if ($clearSubcategory) {
                    $expense->subcategory_id = null;
                } elseif ($subcategoryId) {
                    $expense->subcategory_id = $subcategoryId;
                } elseif ($categoryId && $expense->subcategory_id && ! $validSubcategoryIds->contains((int) $expense->subcategory_id)) {
                    $expense->subcategory_id = null;
                }

                if ($expense->isDirty()) {
                    $expense->save();
                }
            }
        });

        return redirect()
            ->route('expenses.index', $this->filterRedirectQuery($request))
            ->with('ok', 'Se actualizaron ' . $expenses->count() . ' egresos');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->user_id != Auth::id()) abort(403);

        $d = Carbon::parse($expense->date);
        $expense->delete();

        return redirect()->route('expenses.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Egreso eliminado');
    }

    private function filterRedirectQuery(Request $request): array
    {
        return array_filter(
            array_intersect_key($request->query(), array_flip(['month', 'year', 'category_id', 'subcategory_id'])),
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
