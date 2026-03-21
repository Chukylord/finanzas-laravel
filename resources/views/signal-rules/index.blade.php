<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Reglas de señales</span>

            <a href="{{ route('signal-rules.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nueva regla
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Listado</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Las señales se generan con tus reglas activas (por horizonte).
                    </p>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $rules->count() }} reglas</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Nombre</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Horizonte</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fast MA</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Slow MA</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Estado</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-72">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($rules as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ $r->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Creada: {{ $r->created_at->format('d/m/Y') }}
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $r->horizon=='short' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : '' }}
                                        {{ $r->horizon=='medium' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200' : '' }}
                                        {{ $r->horizon=='long' ? 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' : '' }}
                                    ">
                                        {{ strtoupper($r->horizon) }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3">{{ $r->fast_ma }}</td>
                                <td class="px-4 sm:px-6 py-3">{{ $r->slow_ma }}</td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $r->active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                        {{ $r->active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <a class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                                           href="{{ route('signal-rules.edit', $r) }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('signal-rules.destroy', $r) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar regla?')">
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
                                    Todavía no tenés reglas. Creá una para empezar a generar señales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
