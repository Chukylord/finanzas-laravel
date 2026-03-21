<?php

namespace App\Http\Controllers;

use App\Models\Recurring;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecurringController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $categoryId = $request->get('category_id');

        $categories = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        $recurrings = Recurring::with('category')
            ->where('user_id', $userId)
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('active', 'desc')
            ->orderBy('next_date')
            ->orderBy('name')
            ->get();

        return view('recurrings.index', compact('recurrings', 'categories', 'categoryId'));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('recurrings.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0',
            'period'       => 'required|in:monthly,yearly',
            'day_of_month' => 'required|integer|min:1|max:28',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $today = Carbon::today();
        $next = Carbon::now()->startOfMonth()->addDays((int) $request->day_of_month - 1);

        if ($next->lt($today)) {
            $next = $next->addMonth();
        }

        Recurring::create([
            'user_id'      => Auth::id(),
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'amount'       => $request->amount,
            'period'       => $request->period,
            'day_of_month' => $request->day_of_month,
            'next_date'    => $next->toDateString(),
            'active'       => true,
        ]);

        return redirect()->route('recurrings.index')->with('ok', 'Recurrente creado');
    }

    public function edit(Recurring $recurring)
    {
        if ($recurring->user_id != Auth::id()) abort(403);

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('recurrings.edit', compact('recurring', 'categories'));
    }

    public function update(Request $request, Recurring $recurring)
    {
        if ($recurring->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0',
            'period'       => 'required|in:monthly,yearly',
            'day_of_month' => 'required|integer|min:1|max:28',
            'active'       => 'nullable',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $today = Carbon::today();
        $next = Carbon::parse($recurring->next_date)->startOfMonth()->addDays((int) $request->day_of_month - 1);

        if ($next->lt($today)) {
            $next = $next->addMonth();
        }

        $recurring->update([
            'category_id'  => $request->category_id,
            'name'         => $request->name,
            'amount'       => $request->amount,
            'period'       => $request->period,
            'day_of_month' => $request->day_of_month,
            'next_date'    => $next->toDateString(),
            'active'       => $request->has('active'),
        ]);

        return redirect()->route('recurrings.index')->with('ok', 'Recurrente actualizado');
    }

    public function destroy(Recurring $recurring)
    {
        if ($recurring->user_id != Auth::id()) abort(403);

        $recurring->delete();
        return redirect()->route('recurrings.index')->with('ok', 'Recurrente eliminado');
    }

    public function generateExpense(Recurring $recurring)
    {
        if ($recurring->user_id != Auth::id()) abort(403);

        if (!$recurring->active) {
            return redirect()->route('recurrings.index')->with('ok', 'El recurrente está inactivo');
        }

        Expense::create([
            'user_id'     => Auth::id(),
            'category_id' => $recurring->category_id,
            'date'        => $recurring->next_date,
            'amount'      => $recurring->amount,
            'description' => $recurring->name,
            'method'      => 'recurrente',
        ]);

        $next = Carbon::parse($recurring->next_date);

        if ($recurring->period === 'monthly') {
            $next = $next->addMonth();
        } else {
            $next = $next->addYear();
        }

        $next = $next->startOfMonth()->addDays($recurring->day_of_month - 1);

        $recurring->update([
            'next_date' => $next->toDateString(),
        ]);

        return redirect()->route('recurrings.index')->with('ok', 'Egreso generado y próxima fecha actualizada');
    }
}