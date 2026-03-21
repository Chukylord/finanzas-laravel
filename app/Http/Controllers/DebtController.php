<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->get('category_id');

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        $debts = Debt::with('category')
            ->where('user_id', Auth::id())
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('active', 'desc')
            ->orderBy('next_due_date')
            ->orderBy('name')
            ->get();

        return view('debts.index', compact('debts', 'categories', 'categoryId'));
    }

    public function create()
    {
        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('debts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:120',
            'type' => 'required|in:loan,card_installment',
            'total_amount' => 'required|numeric|min:0',
            'installments_total' => 'required|integer|min:1|max:600',
            'installments_paid' => 'nullable|integer|min:0|max:600',
            'installment_amount' => 'required|numeric|min:0',
            'day_of_month' => 'required|integer|min:1|max:28',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $total = (int) $request->installments_total;
        $paid  = (int) ($request->installments_paid ?? 0);

        if ($paid > $total) {
            return back()->withErrors(['installments_paid' => 'Las cuotas pagadas no pueden ser mayores a las cuotas totales.'])->withInput();
        }

        $today = Carbon::today();
        $next = Carbon::now()->startOfMonth()->addDays(((int)$request->day_of_month) - 1);
        if ($next->lt($today)) {
            $next = $next->addMonth();
        }

        $active = $paid < $total;

        Debt::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'name' => $request->name,
            'type' => $request->type,
            'total_amount' => $request->total_amount,
            'installments_total' => $total,
            'installments_paid' => $paid,
            'installment_amount' => $request->installment_amount,
            'day_of_month' => $request->day_of_month,
            'next_due_date' => $next->toDateString(),
            'active' => $active,
        ]);

        return redirect()->route('debts.index')->with('ok', 'Deuda creada');
    }

    public function edit(Debt $debt)
    {
        if ($debt->user_id != Auth::id()) abort(403);

        $categories = Category::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('debts.edit', compact('debt', 'categories'));
    }

    public function update(Request $request, Debt $debt)
    {
        if ($debt->user_id != Auth::id()) abort(403);

        $request->validate([
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:120',
            'type'                => 'required|in:loan,card_installment',
            'total_amount'        => 'required|numeric|min:0',
            'installments_total'  => 'required|integer|min:1|max:600',
            'installments_paid'   => 'required|integer|min:0|max:600',
            'installment_amount'  => 'required|numeric|min:0',
            'day_of_month'        => 'required|integer|min:1|max:28',
            'active'              => 'nullable',
        ]);

        $catOk = Category::where('id', $request->category_id)
            ->where('user_id', Auth::id())
            ->where('type', 'expense')
            ->exists();

        if (!$catOk) {
            return back()->withErrors(['category_id' => 'Categoría inválida'])->withInput();
        }

        $today = Carbon::today();
        $next = Carbon::parse($debt->next_due_date)->startOfMonth()->addDays((int)$request->day_of_month - 1);

        if ($next->lt($today)) {
            $next = $next->addMonth();
        }

        $paid  = (int) $request->installments_paid;
        $total = (int) $request->installments_total;

        $isActive = $request->has('active') && ($paid < $total);

        $debt->update([
            'category_id'        => $request->category_id,
            'name'               => $request->name,
            'type'               => $request->type,
            'total_amount'       => $request->total_amount,
            'installments_total' => $total,
            'installments_paid'  => $paid,
            'installment_amount' => $request->installment_amount,
            'day_of_month'       => $request->day_of_month,
            'next_due_date'      => $next->toDateString(),
            'active'             => $isActive,
        ]);

        return redirect()->route('debts.index')->with('ok', 'Deuda actualizada');
    }

    public function destroy(Debt $debt)
    {
        if ($debt->user_id != Auth::id()) abort(403);

        $debt->delete();
        return redirect()->route('debts.index')->with('ok', 'Deuda eliminada');
    }

    public function payInstallment(Debt $debt)
    {
        if ($debt->user_id != Auth::id()) abort(403);

        if (!$debt->active) {
            return redirect()->route('debts.index')->with('ok', 'La deuda está inactiva');
        }

        if ($debt->installments_paid >= $debt->installments_total) {
            $debt->update(['active' => false]);
            return redirect()->route('debts.index')->with('ok', 'La deuda ya está saldada');
        }

        Expense::create([
            'user_id'     => Auth::id(),
            'category_id' => $debt->category_id,
            'date'        => $debt->next_due_date,
            'amount'      => $debt->installment_amount,
            'description' => $debt->name . ' - Cuota ' . ($debt->installments_paid + 1) . '/' . $debt->installments_total,
            'method'      => 'cuota',
        ]);

        $paid = $debt->installments_paid + 1;

        $next = Carbon::parse($debt->next_due_date)->addMonth();
        $next = $next->startOfMonth()->addDays($debt->day_of_month - 1);

        $active = $paid < $debt->installments_total;

        $debt->update([
            'installments_paid' => $paid,
            'next_due_date'     => $next->toDateString(),
            'active'            => $active,
        ]);

        return redirect()->route('debts.index')->with('ok', 'Cuota pagada y egreso generado');
    }
}