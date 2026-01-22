<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Instrumentos</span>
            <div class="flex gap-2">
                <a href="{{ route('markets.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    ← Mercados
                </a>
                <a href="{{ route('instruments.create') }}" class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    + Nuevo instrumento
                </a>
            </div>
        </div>
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

        {{-- Buscador --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('instruments.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Buscar</label>
                    <input name="q" value="{{ $q }}" placeholder="Ej: GGAL, AAPL, BYMA, BTC..."
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Buscar
                </button>
                <a href="{{ route('instruments.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Limpiar
                </a>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Símbolo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Nombre</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Mercado</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Moneda</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Estado</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-56">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($instruments as $inst)
                            @php
                                $inWatch = in_array($inst->id, $watchIds);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3 font-semibold">{{ $inst->symbol }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $inst->name }}</td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-800">
                                        {{ strtoupper($inst->type) }}
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">{{ $inst->market ?: '—' }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $inst->currency }}</td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs
                                        {{ $inst->active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                        {{ $inst->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-3 justify-end items-center">
                                        <form action="{{ route('watchlist.toggle', $inst) }}" method="POST">
                                            @csrf
                                            <button class="px-3 py-2 rounded-xl text-white
                                                {{ $inWatch ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                                {{ $inWatch ? 'Quitar' : 'Agregar' }}
                                            </button>
                                        </form>

                                        <a class="text-blue-700 hover:underline" href="{{ route('instruments.edit', $inst) }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('instruments.destroy', $inst) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar instrumento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay instrumentos todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</x-app-layout>
