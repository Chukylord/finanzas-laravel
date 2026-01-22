<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Dashboard mensual</span>

            <div class="flex gap-2">
                <a href="{{ route('incomes.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Ingresos
                </a>
                <a href="{{ route('expenses.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Egresos
                </a>
                <a href="{{ route('recurrings.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Recurrentes
                </a>
                <a href="{{ route('debts.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Deudas
                </a>

                <a href="{{ route('markets.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Mercados
                </a>

                <a href="{{ route('investments.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Inversiones
                </a>

                <a href="{{ route('exchange-rates.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Tipo de cambio
                </a>


            </div>
        </div>
    </x-slot>

    @php
        $balancePositive = $balance >= 0;

        $months = [
            1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
            7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
        ];

        $totalExpenseSafe = max((float)$totalExpense, 1);
        $totalIncomeSafe = max((float)$totalIncome, 1);
    @endphp

    <div class="space-y-6">

        {{-- Filtro mes/año --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Mes</label>
                    <select name="month" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>
                                {{ $months[$m] }}
                            </option>
                        @endfor
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

                <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                    Mostrando: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $months[(int)$month] ?? $month }}/{{ $year }}</span>
                </div>
            </form>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ingresos</p>
                        <p class="mt-1 text-2xl font-semibold">${{ number_format($totalIncome, 2, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Total del mes</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200 flex items-center justify-center">
                        ⬆
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('incomes.create') }}" class="text-sm font-medium text-emerald-700 dark:text-emerald-200 hover:underline">
                        + Cargar ingreso
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Egresos</p>
                        <p class="mt-1 text-2xl font-semibold">${{ number_format($totalExpense, 2, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Total del mes</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200 flex items-center justify-center">
                        ⬇
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('expenses.create') }}" class="text-sm font-medium text-rose-700 dark:text-rose-200 hover:underline">
                        + Cargar egreso
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Balance</p>
                        <p class="mt-1 text-2xl font-semibold {{ $balancePositive ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                            ${{ number_format($balance, 2, ',', '.') }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ingresos - Egresos</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl
                        {{ $balancePositive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' }}
                        flex items-center justify-center">
                        {{ $balancePositive ? '✓' : '!' }}
                    </div>
                </div>

                {{-- mini barra --}}
                <div class="mt-4">
                    @php
                        $ratio = min(($totalExpense / $totalIncomeSafe) * 100, 999);
                    @endphp
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span>Gasto / Ingreso</span>
                        <span>{{ number_format($ratio, 0) }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-2 rounded-full bg-gray-900 dark:bg-white" style="width: {{ min($ratio, 100) }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Vencimientos</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $upcoming->count() }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Próximos</p>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200 flex items-center justify-center">
                        ⏳
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('recurrings.index') }}" class="text-sm font-medium text-amber-700 dark:text-amber-200 hover:underline">
                        Ver lista
                    </a>
                </div>
            </div>
        </div>

        {{-- 2 columnas: rankings + vencimientos --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Ingresos por categoría --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold">Ingresos por categoría</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Top</span>
                    </div>

                    @if($incomeByCategory->count() == 0)
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay ingresos en este mes.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($incomeByCategory as $name => $amount)
                                @php
                                    $pct = min(($amount / $totalIncomeSafe) * 100, 100);
                                @endphp
                                <li>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium">{{ $name }}</span>
                                        <span class="font-semibold">${{ number_format($amount, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $pct }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Egresos por categoría --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold">Egresos por categoría</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Top</span>
                    </div>

                    @if($expenseByCategory->count() == 0)
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay egresos en este mes.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($expenseByCategory as $name => $amount)
                                @php
                                    $pct = min(($amount / $totalExpenseSafe) * 100, 100);
                                @endphp
                                <li>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium">{{ $name }}</span>
                                        <span class="font-semibold">${{ number_format($amount, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-2 rounded-full bg-rose-600" style="width: {{ $pct }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Vencimientos próximos --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Vencimientos próximos</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Recurrentes / Cuotas</span>
                </div>

                @if($upcoming->count() == 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay vencimientos próximos.</p>
                @else
                    <div class="space-y-3">
                        @foreach($upcoming as $u)
                            <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $u['name'] }}</div>

                                        @if($u['detail'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u['detail'] }}</div>
                                        @endif

                                        <div class="mt-1 inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                                {{ $u['type'] == 'recurrente' ? 'Recurrente' : 'Cuota' }}
                                            </span>
                                            <span>
                                                {{ \Carbon\Carbon::parse($u['date'])->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="font-semibold">
                                        ${{ number_format($u['amount'], 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
