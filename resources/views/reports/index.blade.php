<x-app-layout>
    @php
        $queryValue = fn ($value) => $value !== null && $value !== '';
        $baseFilterQuery = array_filter([
            'movement_type' => $filters['movement_type'] !== 'all' ? $filters['movement_type'] : null,
            'category_id' => $filters['category_id'],
            'subcategory_id' => $filters['subcategory_id'],
            'method' => $filters['method'],
            'q' => $filters['q'],
        ], $queryValue);
        $exportQuery = array_merge($baseFilterQuery, [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ]);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="text-lg font-semibold">Reportes</span>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}
                </div>
            </div>

            <a href="{{ route('reports.export', $exportQuery) }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-700 text-white hover:bg-emerald-800 text-sm font-semibold">
                Exportar CSV
            </a>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => ((float) $value < 0 ? '-$' : '$') . number_format(abs((float) $value), 2, ',', '.');
        $signedMoney = fn ($value) => ((float) $value > 0 ? '+' : ((float) $value < 0 ? '-' : '')) . '$' . number_format(abs((float) $value), 2, ',', '.');
        $percent = fn ($value) => $value === null ? 'Sin ingresos' : number_format((float) $value, 1, ',', '.') . '%';
    @endphp

    <div class="space-y-6">
        @if (isset($errors) && $errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-900">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($quickFilters as $key => $label)
                        <a href="{{ route('reports.index', array_merge($baseFilterQuery, ['quick' => $key])) }}"
                           class="px-3 py-2 rounded-xl border text-sm {{ $filters['quick'] === $key ? 'bg-gray-900 text-white border-gray-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <a href="{{ route('reports.index', ['quick' => 'current_month']) }}"
                       class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                        Limpiar
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Desde</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                               class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Hasta</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                               class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Tipo</label>
                        <select name="movement_type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="all" {{ $filters['movement_type'] === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="income" {{ $filters['movement_type'] === 'income' ? 'selected' : '' }}>Solo ingresos</option>
                            <option value="expense" {{ $filters['movement_type'] === 'expense' ? 'selected' : '' }}>Solo egresos</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Método</label>
                        <select name="method" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todos</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ $filters['method'] === $method ? 'selected' : '' }}>
                                    {{ $method }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Categoría</label>
                        <select name="category_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (int) $filters['category_id'] === (int) $category->id ? 'selected' : '' }}>
                                    {{ $category->type === 'income' ? 'Ingreso' : 'Egreso' }} / {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Subcategoría</label>
                        <select name="subcategory_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">Todas</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" {{ (int) $filters['subcategory_id'] === (int) $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->category?->name ?? 'Sin categoría' }} / {{ $subcategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Texto en descripción</label>
                        <div class="flex gap-2">
                            <input type="text" name="q" value="{{ $filters['q'] }}"
                                   placeholder="Buscar por descripción"
                                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                            <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black whitespace-nowrap">
                                Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ingresos filtrados</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-200">{{ $money($summary['income_total']) }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $summary['income_count'] }} movimiento(s)</div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Egresos filtrados</div>
                <div class="mt-1 text-2xl font-semibold text-rose-700 dark:text-rose-200">{{ $money($summary['expense_total']) }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $summary['expense_count'] }} movimiento(s)</div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Balance / ahorro</div>
                <div class="mt-1 text-2xl font-semibold {{ $summary['balance'] >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    {{ $money($summary['balance']) }}
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Ingresos - egresos</div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Tasa de ahorro</div>
                <div class="mt-1 text-2xl font-semibold {{ $summary['savings_rate'] !== null && $summary['savings_rate'] >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    {{ $percent($summary['savings_rate']) }}
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Sobre ingresos filtrados</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Promedio de ingreso</div>
                <div class="mt-1 text-xl font-semibold">{{ $money($summary['income_average']) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Promedio de egreso</div>
                <div class="mt-1 text-xl font-semibold">{{ $money($summary['expense_average']) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <x-report-summary-table
                title="Ingresos por categoría"
                :rows="$incomeCategorySummary"
                empty="Sin ingresos para los filtros aplicados."
                :money="$money"
            />

            <x-report-summary-table
                title="Egresos por categoría"
                :rows="$expenseCategorySummary"
                empty="Sin egresos para los filtros aplicados."
                :money="$money"
            />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <x-report-subcategory-table
                title="Ingresos por subcategoría"
                :rows="$incomeSubcategorySummary"
                empty="Sin ingresos con subcategorías para los filtros aplicados."
                :money="$money"
            />

            <x-report-subcategory-table
                title="Egresos por subcategoría"
                :rows="$expenseSubcategorySummary"
                empty="Sin egresos con subcategorías para los filtros aplicados."
                :money="$money"
            />
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-semibold">Detalle de movimientos</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $movements->count() }} movimiento(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Subcategoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Descripción</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Método</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3 whitespace-nowrap">{{ $movement['date']->format('d/m/Y') }}</td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold {{ $movement['type'] === 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' }}">
                                        {{ $movement['type_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">{{ $movement['category'] }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $movement['subcategory'] }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $movement['description'] ?: '-' }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $movement['method'] ?: '-' }}</td>
                                <td class="px-4 sm:px-6 py-3 text-right font-semibold {{ $movement['signed_amount'] >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                                    {{ $signedMoney($movement['signed_amount']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No hay movimientos para los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
