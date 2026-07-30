<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Informes inversión</span>

            <a href="{{ route('investments.index') }}"
               class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                ← Inversiones
            </a>
        </div>
    </x-slot>

    @php
        $months = [
            1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
            7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
        ];

        $typeLabel = [
            'deposit' => 'Depósito',
            'withdraw' => 'Retiro',
            'profit' => 'Ganancia',
            'loss' => 'Pérdida',
            'fee' => 'Comisión',
            'adjust' => 'Ajuste',
        ];
    @endphp

    <div class="space-y-5">

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('investment-reports.index') }}" class="flex flex-wrap gap-3 items-end">
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
                    <input type="number"
                           name="year"
                           value="{{ $year }}"
                           class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 w-28">
                </div>

                <div class="min-w-[260px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Cuenta</label>
                    <select name="account_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todas</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string)$accountId === (string)$account->id ? 'selected' : '' }}>
                                {{ $account->name }} · {{ $account->type ?? 'Cuenta' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Filtrar
                </button>

                <a href="{{ route('investment-reports.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Limpiar
                </a>
            </form>
        </div>

        @if($exchangeRate)
            <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-900/20 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-semibold text-indigo-800 dark:text-indigo-200">
                        Dólar utilizado: ${{ number_format($usdArs, 4, ',', '.') }} — fecha {{ $exchangeRate->date->format('d/m/Y') }}
                    </div>
                    <div class="mt-1 text-sm text-indigo-700 dark:text-indigo-300">
                        Es la última cotización propia disponible hasta el cierre del período, nunca una cotización posterior.
                    </div>
                </div>
                <a href="{{ route('exchange-rates.index') }}"
                   class="px-3 py-2 rounded-xl border border-indigo-200 dark:border-indigo-800 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/40">
                    Ver cotizaciones
                </a>
            </div>
        @else
            <div class="rounded-2xl border border-amber-100 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <div class="font-semibold text-amber-800 dark:text-amber-200">
                    Todavía no cargaste una cotización del dólar
                </div>
                <a href="{{ route('exchange-rates.index') }}"
                   class="px-3 py-2 rounded-xl border border-amber-200 dark:border-amber-800 text-sm font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40">
                    Cargar cotización
                </a>
            </div>
        @endif

        {{-- Cards principales --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ahorro neto ARS</p>
                <p class="mt-2 text-2xl font-bold {{ $summary['ars']['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    ${{ number_format($summary['ars']['net'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Depósitos, ganancias y ajustes menos retiros, pérdidas y comisiones.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Ahorro neto USD</p>
                <p class="mt-2 text-2xl font-bold {{ $summary['usd']['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                    USD {{ number_format($summary['usd']['net'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Saldo neto mensual en dólares.
                </p>
                @if($usdNetArs !== null)
                    <p class="mt-2 text-xs font-medium text-indigo-700 dark:text-indigo-200">
                        Equivale a ${{ number_format($usdNetArs, 2, ',', '.') }} ARS
                    </p>
                @else
                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-200">Sin cotización</p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">USD comprados en el mes</p>
                <p class="mt-2 text-2xl font-bold text-indigo-700 dark:text-indigo-200">
                    USD {{ number_format($usdBought, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Suma de depósitos en USD.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Movimientos del mes</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $movements->count() }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $months[(int)$month] ?? $month }} / {{ $year }}
                </p>
            </div>
        </div>

        {{-- Desglose por moneda --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <h2 class="font-semibold mb-3">Resumen ARS</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Depósitos</span>
                        <span class="font-semibold">${{ number_format($summary['ars']['deposits'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Retiros</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-${{ number_format($summary['ars']['withdraws'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Ganancias</span>
                        <span class="font-semibold text-emerald-700 dark:text-emerald-200">${{ number_format($summary['ars']['profits'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Pérdidas</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-${{ number_format($summary['ars']['losses'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Comisiones</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-${{ number_format($summary['ars']['fees'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Ajustes</span>
                        <span class="font-semibold">${{ number_format($summary['ars']['adjusts'], 2, ',', '.') }}</span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-800 pt-2 flex justify-between">
                        <span class="font-semibold">Neto</span>
                        <span class="font-bold">${{ number_format($summary['ars']['net'], 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <h2 class="font-semibold mb-3">Resumen USD</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Depósitos / compras</span>
                        <span class="font-semibold">USD {{ number_format($summary['usd']['deposits'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Retiros</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-USD {{ number_format($summary['usd']['withdraws'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Ganancias</span>
                        <span class="font-semibold text-emerald-700 dark:text-emerald-200">USD {{ number_format($summary['usd']['profits'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Pérdidas</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-USD {{ number_format($summary['usd']['losses'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Comisiones</span>
                        <span class="font-semibold text-rose-700 dark:text-rose-200">-USD {{ number_format($summary['usd']['fees'], 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Ajustes</span>
                        <span class="font-semibold">USD {{ number_format($summary['usd']['adjusts'], 2, ',', '.') }}</span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-800 pt-2 flex justify-between">
                        <span class="font-semibold">Neto</span>
                        <span class="font-bold">USD {{ number_format($summary['usd']['net'], 2, ',', '.') }}</span>
                    </div>
                    @if($combinedNetArs !== null)
                        <div class="border-t border-gray-200 dark:border-gray-800 pt-2 flex justify-between">
                            <span class="font-semibold">Neto combinado en ARS</span>
                            <span class="font-bold">${{ number_format($combinedNetArs, 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Por cuenta --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Resumen por cuenta</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $byAccount->count() }} cuentas</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Cuenta</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Neto ARS</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Neto USD</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Equiv. ARS</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">USD comprados</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Mov.</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($byAccount as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3 font-medium">
                                    {{ $row['account']?->name ?? '—' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($row['ars_net'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    USD {{ number_format($row['usd_net'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    @if($row['ars_equiv'] !== null)
                                        ${{ number_format($row['ars_equiv'], 2, ',', '.') }}
                                    @else
                                        <span class="text-gray-400">Sin cotización</span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    USD {{ number_format($row['usd_bought'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    {{ $row['count'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay movimientos para el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detalle de movimientos --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Detalle de movimientos</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $movements->count() }} items</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Cuenta</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Moneda</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Detalle</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($movements as $m)
                            @php
                                $isNegative = in_array($m->type, ['withdraw','loss','fee']);
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $m->account?->name ?? '—' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                        {{ $m->type=='deposit' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : '' }}
                                        {{ $m->type=='withdraw' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' : '' }}
                                        {{ $m->type=='profit' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200' : '' }}
                                        {{ $m->type=='loss' ? 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' : '' }}
                                        {{ $m->type=='fee' ? 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-200' : '' }}
                                        {{ $m->type=='adjust' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' : '' }}
                                    ">
                                        {{ $typeLabel[$m->type] ?? $m->type }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $m->currency }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    <span class="{{ $isNegative ? 'text-rose-700 dark:text-rose-200' : 'text-emerald-700 dark:text-emerald-200' }}">
                                        {{ $isNegative ? '-' : '+' }}
                                        {{ $m->currency === 'USD' ? 'USD ' : '$' }}{{ number_format($m->amount, 2, ',', '.') }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $m->description ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay movimientos para el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
