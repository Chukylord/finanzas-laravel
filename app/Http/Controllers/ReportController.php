<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Debt;
use App\Models\Recurring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $from = $request->input('from', $today->copy()->startOfMonth()->toDateString());
        $to   = $request->input('to', $today->copy()->endOfMonth()->toDateString());

        // checkboxes: sections[]=incomes&sections[]=expenses...
        $sections = $request->input('sections', ['incomes', 'expenses']); // default: ingresos+egresos

        // Validación simple
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'sections' => 'nullable|array',
        ]);

        $userId = Auth::id();

        // Resultados (solo se cargan si el usuario seleccionó esa sección)
        $data = [
            'incomes' => collect(),
            'expenses' => collect(),
            'debts' => collect(),
            'recurrings' => collect(),
            'categories' => collect(),
        ];

        // Totales
        $totals = [
            'income' => 0,
            'expense' => 0,
            'balance' => 0,
        ];

        // Charts (arrays listos para Chart.js)
        $charts = [
            'labels' => [],
            'incomeByDay' => [],
            'expenseByDay' => [],
            'expenseByCategoryLabels' => [],
            'expenseByCategoryValues' => [],
            'incomeByCategoryLabels' => [],
            'incomeByCategoryValues' => [],
        ];

        // INCOMES
        if (in_array('incomes', $sections)) {
            $data['incomes'] = Income::with('category')
                ->where('user_id', $userId)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->get();

            $totals['income'] = (float) $data['incomes']->sum('amount');
        }

        // EXPENSES
        if (in_array('expenses', $sections)) {
            $data['expenses'] = Expense::with('category')
                ->where('user_id', $userId)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->get();

            $totals['expense'] = (float) $data['expenses']->sum('amount');
        }

        $totals['balance'] = $totals['income'] - $totals['expense'];

        // DEBTS (cuotas) -> usamos next_due_date como “fecha”
        if (in_array('debts', $sections)) {
            $data['debts'] = Debt::with('category')
                ->where('user_id', $userId)
                ->whereBetween('next_due_date', [$from, $to])
                ->orderBy('next_due_date')
                ->get();
        }

        // RECURRINGS -> usamos next_date como “fecha”
        if (in_array('recurrings', $sections)) {
            $data['recurrings'] = Recurring::with('category')
                ->where('user_id', $userId)
                ->whereBetween('next_date', [$from, $to])
                ->orderBy('next_date')
                ->get();
        }

        // CATEGORIES (no depende fechas, pero lo mostramos si lo quiere)
        if (in_array('categories', $sections)) {
            $data['categories'] = Category::where('user_id', $userId)
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        }

        // ====== CHARTS ======
        // Timeline por día (Ingresos vs Egresos)
        // armamos labels por cada día entre from y to
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $labels = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $charts['labels'] = $labels;

        // Sumatorias por día
        $incomeByDayMap = collect();
        $expenseByDayMap = collect();

        if (in_array('incomes', $sections)) {
            $incomeByDayMap = $data['incomes']
                ->groupBy('date')
                ->map(fn($rows) => (float) $rows->sum('amount'));
        }

        if (in_array('expenses', $sections)) {
            $expenseByDayMap = $data['expenses']
                ->groupBy('date')
                ->map(fn($rows) => (float) $rows->sum('amount'));
        }

        $charts['incomeByDay'] = array_map(fn($d) => $incomeByDayMap->get($d, 0), $labels);
        $charts['expenseByDay'] = array_map(fn($d) => $expenseByDayMap->get($d, 0), $labels);

        // Donuts por categoría (ingresos/egresos)
        if (in_array('expenses', $sections)) {
            $byCat = $data['expenses']->groupBy(fn($e) => $e->category?->name ?? 'Sin categoría')
                ->map(fn($rows) => (float) $rows->sum('amount'))
                ->sortDesc();

            $charts['expenseByCategoryLabels'] = $byCat->keys()->values();
            $charts['expenseByCategoryValues'] = $byCat->values()->values();
        }

        if (in_array('incomes', $sections)) {
            $byCat = $data['incomes']->groupBy(fn($i) => $i->category?->name ?? 'Sin categoría')
                ->map(fn($rows) => (float) $rows->sum('amount'))
                ->sortDesc();

            $charts['incomeByCategoryLabels'] = $byCat->keys()->values();
            $charts['incomeByCategoryValues'] = $byCat->values()->values();
        }

        return view('reports.index', compact('from', 'to', 'sections', 'data', 'totals', 'charts'));
    }
}
