<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Egresos</span>
            <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Cargar egreso
            </a>
        </div>
    </x-slot>

    @php
        $months = [
            1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
            7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
        ];
        $totalExpenseList = $expenses->sum('amount');
    @endphp

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filtro mes/año --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap gap-3 items-end">
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

                <a href="{{ route('expenses.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Mes actual
                </a>

                <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                    Total: <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalExpenseList, 2, ',', '.') }}</span>
                    <span class="mx-2">·</span>
                    {{ $months[(int)$month] ?? $month }}/{{ $year }}
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Detalle</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Método</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-44">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($expenses as $e)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($e->date)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    {{ $e->category?->name ?? '-' }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $e->description ?: '-' }}
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800">
                                        {{ $e->method ?: 'manual' }}
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($e->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-3 justify-end">
                                        <a class="text-blue-700 hover:underline"
                                           href="{{ route('expenses.edit', $e) }}?month={{ $month }}&year={{ $year }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('expenses.destroy', $e) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar egreso?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay egresos para este mes.
                                </td>
                            </tr>
                        @endforelse

                        @if($expenses->count() > 0)
                            <tr class="bg-gray-50 dark:bg-gray-800/30">
                                <td class="px-4 sm:px-6 py-3 font-semibold" colspan="4">TOTAL</td>
                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    ${{ number_format($totalExpenseList, 2, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
