<?php

namespace App\Http\Controllers;

use App\Models\Recurring;
use App\Models\Debt;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Mes/año seleccionados (por defecto el actual)
        $month = (int) ($request->get('month') ?? date('m'));
        $year  = (int) ($request->get('year') ?? date('Y'));

        $userId = Auth::id();

        $incomes = Income::with('category')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Totales por categoría (para mostrar ranking)
        $incomeByCategory = $incomes->groupBy(fn($i) => $i->category?->name ?? 'Sin categoría')
            ->map(fn($items) => $items->sum('amount'))
            ->sortDesc();

        $expenseByCategory = $expenses->groupBy(fn($e) => $e->category?->name ?? 'Sin categoría')
            ->map(fn($items) => $items->sum('amount'))
            ->sortDesc();

        // Vencimientos próximos (recurrentes + deudas)
        $today = Carbon::today();

        $upcomingRecurrings = Recurring::where('user_id', $userId)
            ->where('active', true)
            ->whereDate('next_date', '>=', $today)
            ->orderBy('next_date')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                return [
                    'date' => $r->next_date,
                    'type' => 'recurrente',
                    'name' => $r->name,
                    'amount' => (float) $r->amount,
                    'detail' => null,
                ];
            });

        $upcomingDebts = Debt::where('user_id', $userId)
            ->where('active', true)
            ->whereDate('next_due_date', '>=', $today)
            ->orderBy('next_due_date')
            ->limit(10)
            ->get()
            ->map(function ($d) {
                $nextN = $d->installments_paid + 1;
                return [
                    'date' => $d->next_due_date,
                    'type' => 'cuota',
                    'name' => $d->name,
                    'amount' => (float) $d->installment_amount,
                    'detail' => 'Cuota ' . $nextN . '/' . $d->installments_total,
                ];
            });

        $upcoming = $upcomingRecurrings
            ->concat($upcomingDebts)
            ->sortBy('date')
            ->values()
            ->take(10);

        return view('dashboard', compact(
            'month','year',
            'totalIncome','totalExpense','balance',
            'incomeByCategory','expenseByCategory',
            'upcoming'
        ));
    }
}
