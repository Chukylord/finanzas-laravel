<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Recurring;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $month = (int) $request->get('month', $today->month);
        $year = (int) $request->get('year', $today->year);

        if ($month < 1 || $month > 12) {
            $month = $today->month;
        }

        if ($year < 2000 || $year > 2100) {
            $year = $today->year;
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $previousStart = $periodStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $userId = Auth::id();

        $incomes = Income::with('category')
            ->where('user_id', $userId)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $expenses = Expense::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $totalIncome = (float) $incomes->sum('amount');
        $totalExpense = (float) $expenses->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? ($balance / $totalIncome) * 100 : null;

        $previousIncome = (float) Income::where('user_id', $userId)
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->sum('amount');

        $previousExpense = (float) Expense::where('user_id', $userId)
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->sum('amount');

        $previousBalance = $previousIncome - $previousExpense;

        $comparisons = [
            'income' => $this->compareMetric($totalIncome, $previousIncome, true),
            'expense' => $this->compareMetric($totalExpense, $previousExpense, false),
            'balance' => $this->compareMetric($balance, $previousBalance, true),
        ];

        $incomeByCategory = $incomes
            ->groupBy(fn ($income) => $income->category?->name ?? 'Sin categoria')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc();

        $expenseByCategory = $expenses
            ->groupBy(fn ($expense) => $expense->category?->name ?? 'Sin categoria')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc();

        $expenseBySubcategory = $expenses
            ->groupBy(function ($expense) {
                $category = $expense->category?->name ?? 'Sin categoria';
                $subcategory = $expense->subcategory?->name ?? 'Sin subcategoria';

                return "{$category} / {$subcategory}";
            })
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc();

        $daysInMonth = $periodStart->daysInMonth;
        if ($periodEnd->lt($today)) {
            $daysElapsed = $daysInMonth;
        } elseif ($periodStart->gt($today)) {
            $daysElapsed = 0;
        } else {
            $daysElapsed = min($today->day, $daysInMonth);
        }

        $daysRemaining = max(0, $daysInMonth - $daysElapsed);
        $dailyAverageExpense = $daysElapsed > 0 ? $totalExpense / $daysElapsed : 0;
        $dailyAvailable = $daysRemaining > 0 ? $balance / $daysRemaining : null;

        $dailySummary = [
            'days_in_month' => $daysInMonth,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'average_expense' => $dailyAverageExpense,
            'available_per_remaining_day' => $dailyAvailable,
            'is_negative' => $dailyAvailable !== null && $dailyAvailable < 0,
        ];

        $next7 = $today->copy()->addDays(7);
        $next30 = $today->copy()->addDays(30);

        $debtBase = Debt::where('user_id', $userId)
            ->where('active', true);

        $recurringBase = Recurring::where('user_id', $userId)
            ->where('active', true);

        $debtAlerts = [
            'overdue' => (clone $debtBase)
                ->whereDate('next_due_date', '<', $today)
                ->orderBy('next_due_date')
                ->get(),
            'next7' => (clone $debtBase)
                ->whereBetween('next_due_date', [$today->toDateString(), $next7->toDateString()])
                ->orderBy('next_due_date')
                ->get(),
            'next30' => (clone $debtBase)
                ->whereBetween('next_due_date', [$today->toDateString(), $next30->toDateString()])
                ->orderBy('next_due_date')
                ->get(),
            'lastInstallment' => (clone $debtBase)
                ->whereRaw('(installments_total - installments_paid) = 1')
                ->orderBy('next_due_date')
                ->get(),
        ];

        $recurringAlerts = [
            'overdue' => (clone $recurringBase)
                ->whereDate('next_date', '<', $today)
                ->orderBy('next_date')
                ->get(),
            'next7' => (clone $recurringBase)
                ->whereBetween('next_date', [$today->toDateString(), $next7->toDateString()])
                ->orderBy('next_date')
                ->get(),
            'next30' => (clone $recurringBase)
                ->whereBetween('next_date', [$today->toDateString(), $next30->toDateString()])
                ->orderBy('next_date')
                ->get(),
        ];

        return view('dashboard', compact(
            'month',
            'year',
            'periodStart',
            'periodEnd',
            'previousStart',
            'previousEnd',
            'today',
            'totalIncome',
            'totalExpense',
            'balance',
            'savingsRate',
            'comparisons',
            'dailySummary',
            'incomeByCategory',
            'expenseByCategory',
            'expenseBySubcategory',
            'debtAlerts',
            'recurringAlerts'
        ));
    }

    private function compareMetric(float $current, float $previous, bool $higherIsBetter): array
    {
        $diff = $current - $previous;
        $percent = $previous > 0 ? ($diff / $previous) * 100 : null;

        if (abs($diff) < 0.01) {
            $status = 'flat';
        } else {
            $status = ($higherIsBetter ? $diff > 0 : $diff < 0) ? 'better' : 'worse';
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'diff' => $diff,
            'percent' => $percent,
            'status' => $status,
        ];
    }
}
