<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Tipo de cambio (USD → ARS)</span>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-900/20 p-5">
            <div class="text-sm font-medium text-indigo-700 dark:text-indigo-200">Última cotización disponible</div>
            @if($latestRate)
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    ${{ number_format($latestRate->usd_ars, 4, ',', '.') }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Fecha {{ $latestRate->date->format('d/m/Y') }}
                </div>
            @else
                <div class="mt-1 font-semibold text-gray-700 dark:text-gray-200">Sin cotización</div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Todavía no cargaste una cotización del dólar.
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Cargar tipo de cambio</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Ingresá el valor del USD en pesos (ARS). Se guarda por fecha.
                    </p>
                </div>

                @if($todayRate)
                    <div class="px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200 text-sm font-medium">
                        Hoy ({{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}): ${{ number_format($todayRate->usd_ars, 4, ',', '.') }}
                    </div>
                @else
                    <div class="px-3 py-2 rounded-xl bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200 text-sm font-medium">
                        Hoy todavía no cargaste tipo de cambio.
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('exchange-rates.store') }}" class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                @csrf

                <x-form.input
                    label="Fecha"
                    name="date"
                    type="date"
                    :value="old('date', $today)"
                    required
                />

                <x-form.input
                    label="USD/ARS"
                    name="usd_ars"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    max="99999999.9999"
                    placeholder="Ej: 1200"
                    :value="old('usd_ars', $todayRate?->usd_ars)"
                    required
                />

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Guardar
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Historial</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $rates->total() }} registros</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">USD/ARS</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-40">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($rates as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($r->date)->format('d/m/Y') }}</div>
                                    @if(\Carbon\Carbon::parse($r->date)->toDateString() === $today)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Hoy
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($r->usd_ars, 4, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('exchange-rates.edit', $r) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                            Editar
                                        </a>
                                        <form action="{{ route('exchange-rates.destroy', $r) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar este tipo de cambio?')">
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
                                <td class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400" colspan="3">
                                    No hay tipos de cambio cargados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                    {{ $rates->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
