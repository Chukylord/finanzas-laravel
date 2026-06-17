<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) ($request->get('month', now()->month));
        $year  = (int) ($request->get('year', now()->year));

        $categoryId = $request->get('category_id');
        $subcategoryId = $request->get('subcategory_id');

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'income')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->whereHas('category', function ($q) {
                $q->where('type', 'income');
            })
            ->orderBy('name')
            ->get();

        $incomes = Income::with(['category', 'subcategory'])
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($subcategoryId, function ($query) use ($subcategoryId) {
                $query->where('subcategory_id', $subcategoryId);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('incomes.index', compact(
            'incomes',
            'month',
            'year',
            'categories',
            'subcategories',
            'categoryId',
            'subcategoryId'
        ));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'income')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->whereHas('category', function ($q) {
                $q->where('type', 'income');
            })
            ->orderBy('name')
            ->get();

        return view('incomes.create', compact('categories', 'subcategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'date'           => 'required|date',
            'amount'         => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:255',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'income')
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

        Income::create([
            'user_id'        => Auth::id(),
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'date'           => $request->date,
            'amount'         => $request->amount,
            'description'    => $request->description,
        ]);

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

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->whereHas('category', function ($q) {
                $q->where('type', 'income');
            })
            ->orderBy('name')
            ->get();

        return view('incomes.edit', compact('income', 'categories', 'subcategories'));
    }

    public function update(Request $request, Income $income)
    {
        if ($income->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'date'           => 'required|date',
            'amount'         => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:255',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'income')
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

        $income->update([
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'date'           => $request->date,
            'amount'         => $request->amount,
            'description'    => $request->description,
        ]);

        $d = Carbon::parse($request->date);

        return redirect()->route('incomes.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Ingreso actualizado');
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
                ->where('type', 'income')
                ->find($categoryId);

            if (! $category) {
                return back()
                    ->withErrors(['bulk_category_id' => 'La categoría seleccionada no es válida para ingresos.'])
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

        $incomes = Income::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get();

        if ($incomes->count() !== $ids->count()) {
            return back()
                ->withErrors(['selected_ids' => 'Algunos ingresos seleccionados no existen o no pertenecen a tu usuario.'])
                ->withInput();
        }

        $validSubcategoryIds = $categoryId
            ? Subcategory::where('user_id', $userId)->where('category_id', $categoryId)->pluck('id')
            : collect();

        DB::transaction(function () use ($incomes, $categoryId, $clearSubcategory, $subcategoryId, $validSubcategoryIds): void {
            foreach ($incomes as $income) {
                if ($categoryId) {
                    $income->category_id = $categoryId;
                }

                if ($clearSubcategory) {
                    $income->subcategory_id = null;
                } elseif ($subcategoryId) {
                    $income->subcategory_id = $subcategoryId;
                } elseif ($categoryId && $income->subcategory_id && ! $validSubcategoryIds->contains((int) $income->subcategory_id)) {
                    $income->subcategory_id = null;
                }

                if ($income->isDirty()) {
                    $income->save();
                }
            }
        });

        return redirect()
            ->route('incomes.index', $this->filterRedirectQuery($request))
            ->with('ok', 'Se actualizaron ' . $incomes->count() . ' ingresos');
    }

    public function destroy(Income $income)
    {
        if ($income->user_id != Auth::id()) abort(403);

        $d = Carbon::parse($income->date);
        $income->delete();

        return redirect()->route('incomes.index', ['month' => $d->month, 'year' => $d->year])
            ->with('ok', 'Ingreso eliminado');
    }

    private function filterRedirectQuery(Request $request): array
    {
        return array_filter(
            array_intersect_key($request->query(), array_flip(['month', 'year', 'category_id', 'subcategory_id'])),
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
