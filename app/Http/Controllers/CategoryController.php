<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())
            ->withCount(['incomes', 'expenses', 'recurrings', 'debts'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
        ]);

        Category::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('categories.index')->with('ok', 'Categoría creada');
    }

    public function destroy(Category $category)
    {
        if ($category->user_id != Auth::id()) {
            abort(403);
        }

        $usedInIncomes = $category->incomes()->exists();
        $usedInExpenses = $category->expenses()->exists();
        $usedInRecurrings = $category->recurrings()->exists();
        $usedInDebts = $category->debts()->exists();

        if ($usedInIncomes || $usedInExpenses || $usedInRecurrings || $usedInDebts) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'No se puede eliminar una categoría que está en uso.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('ok', 'Categoría eliminada');
    }
}
