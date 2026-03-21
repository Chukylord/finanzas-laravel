<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Egresos</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Listado de egresos registrados.
            </div>

            <a href="{{ route('expenses.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nuevo egreso
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap gap-3 items-end">

                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Mes</label>
                    <select name="month" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Año</label>
                    <input type="number" name="year" value="{{ $year }}"
                           class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 w-28">
                </div>

                <div class="min-w-[220px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Categoría</label>
                    <select name="category_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string)$categoryId === (string)$category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Filtrar
                </button>

                <a href="{{ route('expenses.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Limpiar
                </a>
            </form>
        </div>

        {{-- Resumen --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total filtrado</p>
                    <p class="mt-1 text-2xl font-semibold">
                        ${{ number_format($totalExpense, 2, ',', '.') }}
                    </p>
                </div>
            </div>
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
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $expense->category?->name ?? '—' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $expense->description }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($expense->amount, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('expenses.edit', $expense) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Editar
                                        </a>

                                        <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar egreso?')">
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
                                <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay egresos para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>