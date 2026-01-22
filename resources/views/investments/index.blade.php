<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Inversiones</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Registrá tus cuentas (Ahorros, IOL, Cripto) y sus movimientos.
                <div class="mt-1">
                    @if($usdArs)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                            USD/ARS: {{ number_format($usdArs, 2, ',', '.') }}
                        </span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                            ({{ \Carbon\Carbon::parse($rate->date)->format('d/m/Y') }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                            Sin tipo de cambio cargado (USD→ARS)
                        </span>
                    @endif
                </div>
            </div>

            <a href="{{ route('investments.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nueva cuenta
            </a>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Cuentas</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $accounts->count() }} items</span>
            </div>

            <div class="overflow-x-auto">
                @php
                    $totalArs = 0;
                    $totalUsd = 0;
                    $totalEquiv = 0;
                @endphp

                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Cuenta</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Moneda</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Saldo ARS</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Saldo USD</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Equiv. ARS</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Estado</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-72">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($accounts as $a)
                            @php
                                $b = $balances[$a->id] ?? ['ARS'=>0,'USD'=>0,'ARS_equiv'=>null];

                                $ars = (float) $b['ARS'];
                                $usd = (float) $b['USD'];
                                $equiv = $b['ARS_equiv'];

                                $totalArs += $ars;
                                $totalUsd += $usd;
                                if ($equiv !== null) $totalEquiv += (float) $equiv;
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ $a->name }}</div>
                                    @if($a->notes)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $a->notes }}</div>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                        {{ $a->default_currency=='USD' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                        {{ $a->default_currency }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($ars, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    USD {{ number_format($usd, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    @if($equiv === null)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        ${{ number_format($equiv, 2, ',', '.') }}
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    @if($a->active)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('investments.movements.index', $a) }}"
                                           class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                                            Movimientos
                                        </a>

                                        <a href="{{ route('investments.edit', $a) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Editar
                                        </a>

                                        <form action="{{ route('investments.destroy', $a) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar cuenta y TODOS sus movimientos?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400" colspan="7">
                                    No tenés cuentas de inversión cargadas todavía.
                                </td>
                            </tr>
                        @endforelse

                        @if($accounts->count() > 0)
                            <tr class="bg-gray-50 dark:bg-gray-800/30">
                                <td class="px-4 sm:px-6 py-3 font-semibold" colspan="2">
                                    TOTALES
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    ${{ number_format($totalArs, 2, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    USD {{ number_format($totalUsd, 2, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-extrabold">
                                    @if($usdArs)
                                        ${{ number_format($totalEquiv, 2, ',', '.') }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3" colspan="2"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
