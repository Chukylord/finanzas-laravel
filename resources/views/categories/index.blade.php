<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Categorías</span>
    </x-slot>

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

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Organizá tus ingresos y egresos por categorías.
            </div>

            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nueva categoría
            </a>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Listado</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $categories->count() }} items</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Nombre</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Estado</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Usos</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-44">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($categories as $c)
                            @php
                                $uses = ($c->incomes_count ?? 0)
                                      + ($c->expenses_count ?? 0)
                                      + ($c->recurrings_count ?? 0)
                                      + ($c->debts_count ?? 0);
                                $inUse = $uses > 0;
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3 font-medium">
                                    {{ $c->name }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $c->type=='income'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' }}">
                                        {{ $c->type=='income' ? 'Ingreso' : 'Egreso' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    @if($inUse)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                            En uso
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            Libre
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-gray-600 dark:text-gray-300">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800">
                                            Ingresos: {{ $c->incomes_count ?? 0 }}
                                        </span>
                                        <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800">
                                            Gastos: {{ $c->expenses_count ?? 0 }}
                                        </span>
                                        <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800">
                                            Recurrentes: {{ $c->recurrings_count ?? 0 }}
                                        </span>
                                        <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800">
                                            Deudas: {{ $c->debts_count ?? 0 }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        @if($inUse)
                                            <button
                                                type="button"
                                                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-70"
                                                title="No se puede eliminar porque está en uso"
                                                disabled
                                            >
                                                Eliminar
                                            </button>
                                        @else
                                            <form action="{{ route('categories.destroy', $c) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400" colspan="5">
                                    No hay categorías todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
