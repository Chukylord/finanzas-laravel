<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Movimientos · {{ $investment->name }}</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Historial de depósitos, retiros, ganancias/pérdidas, comisiones.
            </div>

            <div class="flex gap-2">
                <a href="{{ route('investments.index') }}"
                   class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Volver
                </a>

                <a href="{{ route('investments.movements.create', $investment) }}"
                   class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    + Nuevo movimiento
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Listado</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $movements->count() }} items</span>
            </div>

            @php
                $balArs = 0; $balUsd = 0;

                foreach($movements as $m) {
                    $signed = in_array($m->type, ['withdraw','loss','fee']) ? -(float)$m->amount : (float)$m->amount;
                    if($m->currency === 'USD') $balUsd += $signed;
                    else $balArs += $signed;
                }

                $typeLabel = [
                    'deposit' => 'Depósito',
                    'withdraw' => 'Retiro',
                    'profit' => 'Ganancia',
                    'loss' => 'Pérdida',
                    'fee' => 'Comisión',
                    'adjust' => 'Ajuste',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Moneda</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Detalle</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-40">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($movements as $m)
                            @php
                                $isNegative = in_array($m->type, ['withdraw','loss','fee']);
                                $signed = $isNegative ? -(float)$m->amount : (float)$m->amount;
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}
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
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                        {{ $m->currency=='USD' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                        {{ $m->currency }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    <span class="{{ $signed < 0 ? 'text-rose-700 dark:text-rose-200' : 'text-emerald-700 dark:text-emerald-200' }}">
                                        {{ $signed < 0 ? '-' : '+' }}
                                        {{ $m->currency == 'USD' ? 'USD ' : '$' }}{{ number_format(abs($signed), 2, ',', '.') }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-gray-700 dark:text-gray-200">
                                        {{ $m->description ?: '—' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <form action="{{ route('movements.destroy', $m) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar movimiento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400" colspan="6">
                                    No hay movimientos todavía.
                                </td>
                            </tr>
                        @endforelse

                        <tr class="bg-gray-50 dark:bg-gray-800/30">
                            <td class="px-4 sm:px-6 py-3 font-semibold" colspan="3">
                                SALDO (calculado por movimientos)
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-right font-extrabold" colspan="3">
                                ${{ number_format($balArs, 2, ',', '.') }}
                                <span class="mx-2 text-gray-400">·</span>
                                USD {{ number_format($balUsd, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
