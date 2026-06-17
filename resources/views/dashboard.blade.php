<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <span class="text-lg font-semibold">Dashboard mensual</span>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $periodStart->format('d/m/Y') }} al {{ $periodEnd->format('d/m/Y') }}
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('incomes.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Ingresos
                </a>
                <a href="{{ route('expenses.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Egresos
                </a>
                <a href="{{ route('debts.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Deudas
                </a>
                <a href="{{ route('recurrings.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Recurrentes
                </a>
                <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Informes
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $formatMoney = fn ($value) => ((float) $value < 0 ? '-$' : '$') . number_format(abs((float) $value), 2, ',', '.');
        $formatSignedMoney = fn ($value) => ((float) $value > 0 ? '+' : ((float) $value < 0 ? '-' : '')) . '$' . number_format(abs((float) $value), 2, ',', '.');
        $formatPercent = fn ($value) => $value === null ? 'Sin ingresos' : number_format((float) $value, 1, ',', '.') . '%';
        $formatSignedPercent = fn ($value) => $value === null ? '' : (((float) $value > 0 ? '+' : '') . number_format((float) $value, 1, ',', '.') . '%');
        $barWidth = fn ($value, $total) => $total > 0 ? min(100, ((float) $value / (float) $total) * 100) : 0;

        $comparisonClass = function ($item) {
            return match ($item['status']) {
                'better' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-900',
                'worse' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-900',
                default => 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            };
        };

        $comparisonLabel = function ($item) use ($formatSignedMoney, $formatSignedPercent) {
            $percent = $item['percent'] !== null ? ' (' . $formatSignedPercent($item['percent']) . ')' : '';
            return $formatSignedMoney($item['diff']) . $percent . ' vs mes anterior';
        };

        $statusLabel = fn ($item) => match ($item['status']) {
            'better' => 'Mejoro',
            'worse' => 'Empeoro',
            default => 'Sin cambios',
        };

        $alertTotal =
            $debtAlerts['overdue']->count()
            + $debtAlerts['next7']->count()
            + $debtAlerts['lastInstallment']->count()
            + $recurringAlerts['overdue']->count()
            + $recurringAlerts['next7']->count();
    @endphp

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Mes</label>
                    <select name="month" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        @foreach($months as $number => $name)
                            <option value="{{ $number }}" {{ (int) $month === $number ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Año</label>
                    <input type="number" name="year"
                           class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 w-28"
                           value="{{ $year }}">
                </div>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Ver
                </button>

                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Mes actual
                </a>

                <div class="xl:ml-auto text-sm text-gray-500 dark:text-gray-400">
                    Mostrando:
                    <span class="font-semibold text-gray-700 dark:text-gray-200">
                        {{ $months[(int) $month] ?? $month }} {{ $year }}
                    </span>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ingresos del mes</p>
                <p class="mt-2 text-2xl font-semibold">{{ $formatMoney($totalIncome) }}</p>
                <div class="mt-4 inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $comparisonClass($comparisons['income']) }}">
                    {{ $statusLabel($comparisons['income']) }}
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $comparisonLabel($comparisons['income']) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Egresos del mes</p>
                <p class="mt-2 text-2xl font-semibold">{{ $formatMoney($totalExpense) }}</p>
                <div class="mt-4 inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $comparisonClass($comparisons['expense']) }}">
                    {{ $statusLabel($comparisons['expense']) }}
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $comparisonLabel($comparisons['expense']) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ahorro del mes</p>
                <p class="mt-2 text-2xl font-semibold {{ $balance >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    {{ $formatMoney($balance) }}
                </p>
                <div class="mt-4 inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $comparisonClass($comparisons['balance']) }}">
                    {{ $statusLabel($comparisons['balance']) }}
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $comparisonLabel($comparisons['balance']) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Tasa de ahorro</p>
                <p class="mt-2 text-2xl font-semibold {{ $savingsRate !== null && $savingsRate >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    {{ $formatPercent($savingsRate) }}
                </p>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Ahorro sobre ingresos del mes</p>
                @if($savingsRate === null)
                    <p class="mt-2 text-sm text-amber-700 dark:text-amber-200">No hay ingresos cargados en este periodo.</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Promedio diario gastado</p>
                <p class="mt-2 text-2xl font-semibold">{{ $formatMoney($dailySummary['average_expense']) }}</p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Calculado sobre {{ $dailySummary['days_elapsed'] }} dia(s) transcurridos.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Dias del mes</p>
                <div class="mt-2 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
                        <div class="text-xl font-semibold">{{ $dailySummary['days_in_month'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
                        <div class="text-xl font-semibold">{{ $dailySummary['days_elapsed'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Pasaron</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
                        <div class="text-xl font-semibold">{{ $dailySummary['days_remaining'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Restan</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border {{ $dailySummary['is_negative'] ? 'border-rose-200 dark:border-rose-900' : 'border-gray-200 dark:border-gray-800' }} rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Disponible por dia restante</p>
                <p class="mt-2 text-2xl font-semibold {{ $dailySummary['is_negative'] ? 'text-rose-700 dark:text-rose-200' : 'text-emerald-700 dark:text-emerald-200' }}">
                    {{ $dailySummary['available_per_remaining_day'] === null ? 'Mes cerrado' : $formatMoney($dailySummary['available_per_remaining_day']) }}
                </p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Ahorro actual dividido por dias restantes.
                </p>
                @if($dailySummary['is_negative'])
                    <p class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-200">
                        El ahorro actual es negativo; conviene revisar egresos pendientes.
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Deudas vencidas</p>
                <p class="mt-2 text-2xl font-semibold {{ $debtAlerts['overdue']->count() > 0 ? 'text-rose-700 dark:text-rose-200' : '' }}">{{ $debtAlerts['overdue']->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Deudas proximos 7 dias</p>
                <p class="mt-2 text-2xl font-semibold {{ $debtAlerts['next7']->count() > 0 ? 'text-amber-700 dark:text-amber-200' : '' }}">{{ $debtAlerts['next7']->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Cuotas en ultima cuota</p>
                <p class="mt-2 text-2xl font-semibold">{{ $debtAlerts['lastInstallment']->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Alertas principales</p>
                <p class="mt-2 text-2xl font-semibold {{ $alertTotal > 0 ? 'text-amber-700 dark:text-amber-200' : '' }}">{{ $alertTotal }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Top egresos por categoria</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 5</span>
                </div>

                @forelse($expenseByCategory->take(5) as $name => $amount)
                    <div class="mb-4 last:mb-0">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-medium truncate">{{ $name }}</span>
                            <span class="font-semibold">{{ $formatMoney($amount) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-2 rounded-full bg-rose-600" style="width: {{ $barWidth($amount, max($totalExpense, 1)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay egresos en este mes.</p>
                @endforelse
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Top egresos por subcategoria</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 5</span>
                </div>

                @forelse($expenseBySubcategory->take(5) as $name => $amount)
                    <div class="mb-4 last:mb-0">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-medium truncate">{{ $name }}</span>
                            <span class="font-semibold">{{ $formatMoney($amount) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $barWidth($amount, max($totalExpense, 1)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay subcategorias con egresos en este mes.</p>
                @endforelse
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Top ingresos por categoria</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 5</span>
                </div>

                @forelse($incomeByCategory->take(5) as $name => $amount)
                    <div class="mb-4 last:mb-0">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-medium truncate">{{ $name }}</span>
                            <span class="font-semibold">{{ $formatMoney($amount) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $barWidth($amount, max($totalIncome, 1)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay ingresos en este mes.</p>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="font-semibold">Alertas de cuotas / deudas</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        30 dias: {{ $debtAlerts['next30']->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-5 py-3">Deuda</th>
                                <th class="text-left font-medium px-5 py-3">Fecha</th>
                                <th class="text-right font-medium px-5 py-3">Cuota</th>
                                <th class="text-left font-medium px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($debtAlerts['overdue']->concat($debtAlerts['next30'])->unique('id')->take(10) as $debt)
                                @php
                                    $dueDate = \Carbon\Carbon::parse($debt->next_due_date);
                                    $isOverdue = $dueDate->lt($today);
                                    $remaining = max(0, $debt->installments_total - $debt->installments_paid);
                                @endphp
                                <tr>
                                    <td class="px-5 py-3">
                                        <div class="font-medium">{{ $debt->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Restan {{ $remaining }} de {{ $debt->installments_total }}</div>
                                    </td>
                                    <td class="px-5 py-3">{{ $dueDate->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ $formatMoney($debt->installment_amount) }}</td>
                                    <td class="px-5 py-3">
                                        @if($isOverdue)
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200">Vencida</span>
                                        @elseif($remaining === 1)
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">Ultima cuota</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">Proxima</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No hay deudas vencidas ni vencimientos en los proximos 30 dias.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="font-semibold">Alertas de recurrentes</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        30 dias: {{ $recurringAlerts['next30']->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-5 py-3">Recurrente</th>
                                <th class="text-left font-medium px-5 py-3">Fecha</th>
                                <th class="text-right font-medium px-5 py-3">Importe</th>
                                <th class="text-left font-medium px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recurringAlerts['overdue']->concat($recurringAlerts['next30'])->unique('id')->take(10) as $recurring)
                                @php
                                    $nextDate = \Carbon\Carbon::parse($recurring->next_date);
                                    $isOverdue = $nextDate->lt($today);
                                @endphp
                                <tr>
                                    <td class="px-5 py-3">
                                        <div class="font-medium">{{ $recurring->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $recurring->period === 'yearly' ? 'Anual' : 'Mensual' }}</div>
                                    </td>
                                    <td class="px-5 py-3">{{ $nextDate->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ $formatMoney($recurring->amount) }}</td>
                                    <td class="px-5 py-3">
                                        @if($isOverdue)
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200">Vencido</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">Proximo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No hay recurrentes vencidos ni vencimientos en los proximos 30 dias.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
