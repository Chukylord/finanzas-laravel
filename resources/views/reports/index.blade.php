<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Informes</span>
    </x-slot>

    <div class="space-y-6">

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Desde</label>
                        <input type="date" name="from" value="{{ $from }}"
                               class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Hasta</label>
                        <input type="date" name="to" value="{{ $to }}"
                               class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    </div>

                    <div class="flex items-end gap-2">
                        <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                            Ver informe
                        </button>

                        <a href="{{ route('reports.index') }}"
                           class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            Reset
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @php
                        $opts = [
                            'incomes' => 'Ingresos',
                            'expenses' => 'Egresos',
                            'debts' => 'Cuotas / Deudas',
                            'recurrings' => 'Recurrentes',
                            'categories' => 'Categorías',
                        ];
                    @endphp

                    @foreach($opts as $key => $label)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                            <input type="checkbox" name="sections[]" value="{{ $key }}"
                                   class="rounded"
                                   {{ in_array($key, $sections ?? []) ? 'checked' : '' }}>
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

            </form>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ingresos</div>
                <div class="mt-1 text-2xl font-semibold">${{ number_format($totals['income'], 2, ',', '.') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Egresos</div>
                <div class="mt-1 text-2xl font-semibold">${{ number_format($totals['expense'], 2, ',', '.') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Balance</div>
                <div class="mt-1 text-2xl font-semibold">${{ number_format($totals['balance'], 2, ',', '.') }}</div>
                <div class="text-xs mt-2 {{ $totals['balance'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $totals['balance'] >= 0 ? 'Positivo' : 'Negativo' }}
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Ingresos vs Egresos (por día)</h2>
                    <span class="text-xs text-gray-500">{{ $from }} → {{ $to }}</span>
                </div>
                <canvas id="chartLine" height="110"></canvas>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Egresos por categoría</h2>
                <canvas id="chartExpenseDonut" height="180"></canvas>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Ingresos por categoría</h2>
                <canvas id="chartIncomeDonut" height="180"></canvas>
            </div>
        </div>

        {{-- Secciones --}}
        @if(in_array('incomes', $sections))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="font-semibold">Ingresos</h2>
                    <span class="text-sm text-gray-500">{{ $data['incomes']->count() }} items</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-6 py-3">Fecha</th>
                                <th class="text-left font-medium px-6 py-3">Categoría</th>
                                <th class="text-left font-medium px-6 py-3">Descripción</th>
                                <th class="text-right font-medium px-6 py-3">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['incomes'] as $i)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-6 py-3">{{ \Carbon\Carbon::parse($i->date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3">{{ $i->category?->name }}</td>
                                    <td class="px-6 py-3">{{ $i->description }}</td>
                                    <td class="px-6 py-3 text-right font-semibold">${{ number_format($i->amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">Sin ingresos en el rango.</td></tr>
                            @endforelse
                            <tr class="bg-gray-50 dark:bg-gray-800/30">
                                <td colspan="3" class="px-6 py-3 font-semibold">TOTAL</td>
                                <td class="px-6 py-3 text-right font-bold">${{ number_format($data['incomes']->sum('amount'), 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(in_array('expenses', $sections))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="font-semibold">Egresos</h2>
                    <span class="text-sm text-gray-500">{{ $data['expenses']->count() }} items</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-6 py-3">Fecha</th>
                                <th class="text-left font-medium px-6 py-3">Categoría</th>
                                <th class="text-left font-medium px-6 py-3">Descripción</th>
                                <th class="text-right font-medium px-6 py-3">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['expenses'] as $e)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-6 py-3">{{ \Carbon\Carbon::parse($e->date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3">{{ $e->category?->name }}</td>
                                    <td class="px-6 py-3">{{ $e->description }}</td>
                                    <td class="px-6 py-3 text-right font-semibold">${{ number_format($e->amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">Sin egresos en el rango.</td></tr>
                            @endforelse
                            <tr class="bg-gray-50 dark:bg-gray-800/30">
                                <td colspan="3" class="px-6 py-3 font-semibold">TOTAL</td>
                                <td class="px-6 py-3 text-right font-bold">${{ number_format($data['expenses']->sum('amount'), 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(in_array('debts', $sections))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="font-semibold">Cuotas / Deudas (por vencimiento)</h2>
                    <span class="text-sm text-gray-500">{{ $data['debts']->count() }} items</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-6 py-3">Vence</th>
                                <th class="text-left font-medium px-6 py-3">Nombre</th>
                                <th class="text-left font-medium px-6 py-3">Pagadas</th>
                                <th class="text-right font-medium px-6 py-3">Cuota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['debts'] as $d)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-6 py-3">{{ \Carbon\Carbon::parse($d->next_due_date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3">
                                        <div class="font-medium">{{ $d->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $d->category?->name }}</div>
                                    </td>
                                    <td class="px-6 py-3">{{ $d->installments_paid }}/{{ $d->installments_total }}</td>
                                    <td class="px-6 py-3 text-right font-semibold">${{ number_format($d->installment_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">Sin cuotas en el rango.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(in_array('recurrings', $sections))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="font-semibold">Recurrentes (próxima fecha)</h2>
                    <span class="text-sm text-gray-500">{{ $data['recurrings']->count() }} items</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-6 py-3">Próx.</th>
                                <th class="text-left font-medium px-6 py-3">Nombre</th>
                                <th class="text-left font-medium px-6 py-3">Categoría</th>
                                <th class="text-right font-medium px-6 py-3">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['recurrings'] as $r)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-6 py-3">{{ \Carbon\Carbon::parse($r->next_date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3">
                                        <div class="font-medium">{{ $r->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $r->active ? 'Activo' : 'Inactivo' }} · {{ $r->period }}</div>
                                    </td>
                                    <td class="px-6 py-3">{{ $r->category?->name }}</td>
                                    <td class="px-6 py-3 text-right font-semibold">${{ number_format($r->amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">Sin recurrentes en el rango.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(in_array('categories', $sections))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="font-semibold">Categorías</h2>
                    <span class="text-sm text-gray-500">{{ $data['categories']->count() }} items</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-left font-medium px-6 py-3">Tipo</th>
                                <th class="text-left font-medium px-6 py-3">Nombre</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['categories'] as $c)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-6 py-3">{{ $c->type == 'income' ? 'Ingreso' : 'Egreso' }}</td>
                                    <td class="px-6 py-3 font-medium">{{ $c->name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-6 py-6 text-center text-gray-500">Sin categorías.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($charts['labels']);
        const incomeByDay = @json($charts['incomeByDay']);
        const expenseByDay = @json($charts['expenseByDay']);

        const expCatLabels = @json($charts['expenseByCategoryLabels']);
        const expCatValues = @json($charts['expenseByCategoryValues']);

        const incCatLabels = @json($charts['incomeByCategoryLabels']);
        const incCatValues = @json($charts['incomeByCategoryValues']);

        // Line chart: ingresos vs egresos
        new Chart(document.getElementById('chartLine'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Ingresos', data: incomeByDay, tension: 0.25 },
                    { label: 'Egresos', data: expenseByDay, tension: 0.25 },
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: true } },
                scales: {
                    x: { ticks: { maxTicksLimit: 8 } }
                }
            }
        });

        // Donut egresos
        new Chart(document.getElementById('chartExpenseDonut'), {
            type: 'doughnut',
            data: { labels: expCatLabels, datasets: [{ data: expCatValues }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Donut ingresos
        new Chart(document.getElementById('chartIncomeDonut'), {
            type: 'doughnut',
            data: { labels: incCatLabels, datasets: [{ data: incCatValues }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</x-app-layout>
