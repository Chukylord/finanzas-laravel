<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type');
        $categoryId = $request->get('category_id');

        $categories = Category::where('user_id', Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', Auth::id())
            ->when($type, function ($query) use ($type) {
                $query->whereHas('category', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('active', 'desc')
            ->orderBy('name')
            ->get();

        return view('subcategories.index', compact(
            'subcategories',
            'categories',
            'type',
            'categoryId'
        ));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'active' => 'nullable',
        ]);

        $category = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$category) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $exists = Subcategory::where('user_id', Auth::id())
            ->where('category_id', $request->category_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($request->name))])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Ya existe una subcategoría con ese nombre para esa categoría.'])->withInput();
        }

        Subcategory::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'name' => trim($request->name),
            'active' => $request->has('active'),
        ]);

        return redirect()->route('subcategories.index')->with('ok', 'Subcategoría creada');
    }

    public function edit(Subcategory $subcategory)
    {
        if ($subcategory->user_id != Auth::id()) abort(403);

        $categories = Category::where('user_id', Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('subcategories.edit', compact('subcategory', 'categories'));
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        if ($subcategory->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'active' => 'nullable',
        ]);

        $category = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$category) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $exists = Subcategory::where('user_id', Auth::id())
            ->where('category_id', $request->category_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($request->name))])
            ->where('id', '!=', $subcategory->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Ya existe una subcategoría con ese nombre para esa categoría.'])->withInput();
        }

        $subcategory->update([
            'category_id' => $request->category_id,
            'name' => trim($request->name),
            'active' => $request->has('active'),
        ]);

        return redirect()->route('subcategories.index')->with('ok', 'Subcategoría actualizada');
    }

    public function destroy(Subcategory $subcategory)
    {
        if ($subcategory->user_id != Auth::id()) abort(403);

        $inUse = $subcategory->incomes()->exists()
            || $subcategory->expenses()->exists();

        if ($inUse) {
            return redirect()->route('subcategories.index')
                ->with('error', 'No se puede eliminar: la subcategoría ya está usada en ingresos o egresos. Podés desactivarla.');
        }

        $subcategory->delete();

        return redirect()->route('subcategories.index')->with('ok', 'Subcategoría eliminada');
    }
}