<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Subcategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $filters = $this->resolveFilters($request, $userId);
        $report = $this->buildReport($filters, $userId);

        $categories = Category::where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $methods = Income::where('user_id', $userId)
            ->whereNotNull('method')
            ->where('method', '!=', '')
            ->distinct()
            ->pluck('method')
            ->merge(
                Expense::where('user_id', $userId)
                    ->whereNotNull('method')
                    ->where('method', '!=', '')
                    ->distinct()
                    ->pluck('method')
            )
            ->unique()
            ->sort()
            ->values();

        $quickFilters = [
            'current_month' => 'Mes actual',
            'previous_month' => 'Mes anterior',
            'last_3_months' => 'Ultimos 3 meses',
            'current_year' => 'Año actual',
        ];

        return view('reports.index', array_merge($report, compact(
            'filters',
            'categories',
            'subcategories',
            'methods',
            'quickFilters'
        )));
    }

    public function export(Request $request)
    {
        $userId = Auth::id();
        $filters = $this->resolveFilters($request, $userId);
        $report = $this->buildReport($filters, $userId);
        $filename = 'reporte_finanzas_' . Carbon::today()->toDateString() . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Fecha',
                'Tipo',
                'Categoría',
                'Subcategoría',
                'Descripción',
                'Método',
                'Monto',
            ], ';');

            foreach ($report['movements'] as $movement) {
                fputcsv($handle, [
                    $movement['date']->format('Y-m-d'),
                    $movement['type_label'],
                    $movement['category'],
                    $movement['subcategory'],
                    $movement['description'],
                    $movement['method'],
                    number_format((float) $movement['signed_amount'], 2, '.', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveFilters(Request $request, int $userId): array
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'quick' => 'nullable|in:current_month,previous_month,last_3_months,current_year',
            'preset' => 'nullable|in:current_month,previous_month,last_3_months,current_year',
            'movement_type' => 'nullable|in:all,income,expense',
            'category_id' => 'nullable|integer',
            'subcategory_id' => 'nullable|integer',
            'method' => 'nullable|string|max:50',
            'q' => 'nullable|string|max:255',
        ]);

        $today = Carbon::today();
        $quick = $validated['quick'] ?? $validated['preset'] ?? null;

        [$from, $to] = match ($quick) {
            'current_month' => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
            ],
            'previous_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_3_months' => [
                $today->copy()->subMonthsNoOverflow(2)->startOfMonth(),
                $today->copy()->endOfMonth(),
            ],
            'current_year' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
            ],
            default => [
                Carbon::parse($validated['date_from'] ?? $validated['from'] ?? $today->copy()->startOfMonth()->toDateString()),
                Carbon::parse($validated['date_to'] ?? $validated['to'] ?? $today->copy()->endOfMonth()->toDateString()),
            ],
        };

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'date_to' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            ]);
        }

        $movementType = $validated['movement_type'] ?? 'all';
        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $subcategoryId = isset($validated['subcategory_id']) ? (int) $validated['subcategory_id'] : null;
        $method = trim((string) ($validated['method'] ?? ''));
        $text = trim((string) ($validated['q'] ?? ''));

        $category = null;
        if ($categoryId) {
            $category = Category::where('user_id', $userId)->find($categoryId);

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'La categoria seleccionada no es valida.',
                ]);
            }

            if ($movementType !== 'all' && $category->type !== $movementType) {
                throw ValidationException::withMessages([
                    'category_id' => 'La categoria no coincide con el tipo de movimiento.',
                ]);
            }
        }

        if ($subcategoryId) {
            $subcategory = Subcategory::with('category')
                ->where('user_id', $userId)
                ->find($subcategoryId);

            if (! $subcategory) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'La subcategoria seleccionada no es valida.',
                ]);
            }

            if ($categoryId && (int) $subcategory->category_id !== $categoryId) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'La subcategoria no pertenece a la categoria seleccionada.',
                ]);
            }

            if ($movementType !== 'all' && $subcategory->category?->type !== $movementType) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'La subcategoria no coincide con el tipo de movimiento.',
                ]);
            }
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'quick' => $quick,
            'preset' => $quick,
            'movement_type' => $movementType,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'method' => $method,
            'q' => $text,
        ];
    }

    private function buildReport(array $filters, int $userId): array
    {
        $incomes = collect();
        $expenses = collect();

        if (in_array($filters['movement_type'], ['all', 'income'], true)) {
            $incomes = $this->incomeQuery($filters, $userId)->get();
        }

        if (in_array($filters['movement_type'], ['all', 'expense'], true)) {
            $expenses = $this->expenseQuery($filters, $userId)->get();
        }

        $incomeTotal = (float) $incomes->sum('amount');
        $expenseTotal = (float) $expenses->sum('amount');
        $incomeCount = $incomes->count();
        $expenseCount = $expenses->count();

        $summary = [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'balance' => $incomeTotal - $expenseTotal,
            'income_count' => $incomeCount,
            'expense_count' => $expenseCount,
            'income_average' => $incomeCount > 0 ? $incomeTotal / $incomeCount : 0,
            'expense_average' => $expenseCount > 0 ? $expenseTotal / $expenseCount : 0,
            'savings_rate' => $incomeTotal > 0 ? (($incomeTotal - $expenseTotal) / $incomeTotal) * 100 : null,
        ];

        $movements = $this->normalizeMovements($incomes, $expenses);

        return [
            'summary' => $summary,
            'incomeCategorySummary' => $this->summarizeByCategory($incomes, $incomeTotal),
            'expenseCategorySummary' => $this->summarizeByCategory($expenses, $expenseTotal),
            'incomeSubcategorySummary' => $this->summarizeBySubcategory($incomes, $incomeTotal),
            'expenseSubcategorySummary' => $this->summarizeBySubcategory($expenses, $expenseTotal),
            'movements' => $movements,
        ];
    }

    private function incomeQuery(array $filters, int $userId)
    {
        return Income::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$filters['from'], $filters['to']])
            ->when($filters['category_id'], fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['subcategory_id'], fn ($query, $subcategoryId) => $query->where('subcategory_id', $subcategoryId))
            ->when($filters['method'] !== '', fn ($query) => $query->where('method', $filters['method']))
            ->when($filters['q'] !== '', fn ($query) => $query->where('description', 'like', '%' . $filters['q'] . '%'))
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function expenseQuery(array $filters, int $userId)
    {
        return Expense::with(['category', 'subcategory'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$filters['from'], $filters['to']])
            ->when($filters['category_id'], fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['subcategory_id'], fn ($query, $subcategoryId) => $query->where('subcategory_id', $subcategoryId))
            ->when($filters['method'] !== '', fn ($query) => $query->where('method', $filters['method']))
            ->when($filters['q'] !== '', fn ($query) => $query->where('description', 'like', '%' . $filters['q'] . '%'))
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function normalizeMovements($incomes, $expenses)
    {
        $incomeRows = $incomes->map(function ($income) {
            return [
                'id' => (int) $income->id,
                'date' => Carbon::parse($income->date),
                'type' => 'income',
                'type_label' => 'Ingreso',
                'category' => $income->category?->name ?? 'Sin categoria',
                'subcategory' => $income->subcategory?->name ?? 'Sin subcategoria',
                'description' => $income->description ?? '',
                'method' => $income->method ?? '',
                'amount' => (float) $income->amount,
                'signed_amount' => (float) $income->amount,
            ];
        });

        $expenseRows = $expenses->map(function ($expense) {
            return [
                'id' => (int) $expense->id,
                'date' => Carbon::parse($expense->date),
                'type' => 'expense',
                'type_label' => 'Egreso',
                'category' => $expense->category?->name ?? 'Sin categoria',
                'subcategory' => $expense->subcategory?->name ?? 'Sin subcategoria',
                'description' => $expense->description ?? '',
                'method' => $expense->method ?? '',
                'amount' => (float) $expense->amount,
                'signed_amount' => -1 * (float) $expense->amount,
            ];
        });

        return $incomeRows
            ->concat($expenseRows)
            ->sortByDesc(fn ($row) => $row['date']->format('Ymd') . str_pad((string) $row['id'], 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private function summarizeByCategory($items, float $total)
    {
        return $items
            ->groupBy(fn ($item) => $item->category?->id ?? 'none')
            ->map(function ($rows) use ($total) {
                $amount = (float) $rows->sum('amount');

                return [
                    'category' => $rows->first()->category?->name ?? 'Sin categoria',
                    'total' => $amount,
                    'count' => $rows->count(),
                    'percentage' => $total > 0 ? ($amount / $total) * 100 : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function summarizeBySubcategory($items, float $total)
    {
        return $items
            ->groupBy(function ($item) {
                return ($item->category?->id ?? 'none') . '-' . ($item->subcategory?->id ?? 'none');
            })
            ->map(function ($rows) use ($total) {
                $amount = (float) $rows->sum('amount');

                return [
                    'category' => $rows->first()->category?->name ?? 'Sin categoria',
                    'subcategory' => $rows->first()->subcategory?->name ?? 'Sin subcategoria',
                    'total' => $amount,
                    'count' => $rows->count(),
                    'percentage' => $total > 0 ? ($amount / $total) * 100 : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();
    }
}
