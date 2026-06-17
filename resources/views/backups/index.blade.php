<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-lg font-semibold">Backups</span>

            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <button class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black text-sm font-semibold"
                        onclick="return confirm('Generar un backup SQL ahora?')">
                    Generar backup
                </button>
            </form>
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

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">Carpeta</div>
            <div class="mt-1 font-mono text-sm break-all">{{ $backupDirectory }}</div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Backups generados</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($backups) }} archivo(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Archivo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Tamano</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($backups as $backup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3 font-mono text-xs sm:text-sm">{{ $backup['name'] }}</td>
                                <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                                    {{ $backup['modified_at']->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">{{ $backup['size_label'] }}</td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex flex-wrap gap-2 justify-end">
                                        <a href="{{ route('backups.download', $backup['name']) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Descargar
                                        </a>

                                        <form method="POST" action="{{ route('backups.destroy', $backup['name']) }}"
                                              onsubmit="return confirm('Eliminar este backup?')">
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
                                <td colspan="4" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Todavia no hay backups generados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
