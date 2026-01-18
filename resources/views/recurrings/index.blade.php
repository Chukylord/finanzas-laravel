<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Recurrentes</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Suscripciones y gastos fijos (Netflix, alquiler, etc.).
            </div>

            <a href="{{ route('recurrings.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nuevo recurrente
            </a>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Listado</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $recurrings->count() }} items</span>
            </div>

            <div class="overflow-x-auto">
                @php
                    $totalRecurrings = $recurrings->sum('amount');
                    $totalRecurringsActive = $recurrings->where('active', true)->sum('amount');
                @endphp

                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Nombre</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Próx. fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Período</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-80">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($recurrings as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ $r->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Día {{ $r->day_of_month }} · {{ $r->active ? 'Activo' : 'Inactivo' }}
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                                        {{ $r->category?->name ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($r->next_date)->format('d/m/Y') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $r->period=='monthly' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200' : 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' }}">
                                        {{ $r->period=='monthly' ? 'Mensual' : 'Anual' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($r->amount, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <form action="{{ route('recurrings.generateExpense', $r) }}" method="POST"
                                              onsubmit="return confirm('¿Generar egreso y avanzar la fecha?')">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                                                {{ !$r->active ? 'disabled' : '' }}>
                                                Generar
                                            </button>
                                        </form>

                                        <a class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                                           href="{{ route('recurrings.edit', $r) }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('recurrings.destroy', $r) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar recurrente?')">
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
                                <td class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400" colspan="6">
                                    No hay recurrentes cargados todavía.
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-gray-50 dark:bg-gray-800/30">
                            <td class="px-4 sm:px-6 py-3 font-semibold" colspan="4">
                                TOTAL (listado) · Activos: ${{ number_format($totalRecurringsActive, 2, ',', '.') }}
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                ${{ number_format($totalRecurrings, 2, ',', '.') }}
                            </td>
                            <td class="px-4 sm:px-6 py-3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
