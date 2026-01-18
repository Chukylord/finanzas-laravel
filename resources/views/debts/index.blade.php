<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Cuotas / Deudas</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Controlá préstamos y compras en cuotas.
            </div>

            <a href="{{ route('debts.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nueva deuda
            </a>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Listado</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $debts->count() }} items</span>
            </div>

            <div class="overflow-x-auto">
                @php
                    $totalMonthlyInstallments = $debts->where('active', true)->sum('installment_amount');

                    $totalRemaining = $debts->where('active', true)->sum(function($d) {
                        $restan = $d->installments_total - $d->installments_paid;
                        if ($restan < 0) $restan = 0;
                        return $restan * $d->installment_amount;
                    });
                @endphp
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Nombre</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Próx.</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Pagadas</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Restan</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Cuota</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-72">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($debts as $d)
                            @php
                                $restan = $d->installments_total - $d->installments_paid;
                                if($restan < 0) $restan = 0;
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ $d->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $d->category?->name }}</div>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $d->type=='loan' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200' : 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' }}">
                                        {{ $d->type=='loan' ? 'Préstamo' : 'Tarjeta (cuotas)' }}
                                    </span>
                                    @if(!$d->active)
                                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            Saldada
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($d->next_due_date)->format('d/m/Y') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $d->installments_paid }}/{{ $d->installments_total }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $restan }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($d->installment_amount, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <form action="{{ route('debts.pay', $d) }}" method="POST"
                                              onsubmit="return confirm('¿Pagar una cuota y generar el egreso?')">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                                                {{ !$d->active ? 'disabled' : '' }}>
                                                Pagar cuota
                                            </button>
                                        </form>

                                        <a class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                                           href="{{ route('debts.edit', $d) }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('debts.destroy', $d) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar deuda?')">
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
                                    No tenés deudas cargadas todavía.
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-gray-50 dark:bg-gray-800/30">
                            <td class="px-4 sm:px-6 py-3 font-semibold" colspan="5">
                                TOTAL CUOTAS (activos) · Restante estimado: ${{ number_format($totalRemaining, 2, ',', '.') }}
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                ${{ number_format($totalMonthlyInstallments, 2, ',', '.') }}
                            </td>
                            <td class="px-4 sm:px-6 py-3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
