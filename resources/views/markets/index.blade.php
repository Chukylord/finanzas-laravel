<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Mercados</span>
            <div class="flex gap-2">
                <a href="{{ route('instruments.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Instrumentos
                </a>
                <a href="{{ route('signal-rules.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Reglas
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Watchlist --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Tu Watchlist</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Corto / Medio / Largo</span>
            </div>

            @if($watchlist->count() == 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Todavía no agregaste instrumentos. Entrá a “Instrumentos” y agregá algunos.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500 dark:text-gray-400">
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="py-3 pr-4">Símbolo</th>
                                <th class="py-3 pr-4">Nombre</th>
                                <th class="py-3 pr-4">Tipo</th>
                                <th class="py-3 pr-4">Último</th>
                                <th class="py-3 pr-4">Fecha</th>
                                <th class="py-3 pr-4">Horizonte</th>
                                <th class="py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($watchlist as $w)
                                @php
                                    $inst = $w->instrument;
                                    $last = $lastPrices[$inst->id] ?? null;
                                @endphp
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="py-3 pr-4 font-semibold">{{ $inst->symbol }}</td>
                                    <td class="py-3 pr-4">{{ $inst->name }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-xs">
                                            {{ strtoupper($inst->type) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $last ? '$'.number_format($last->close, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">
                                        {{ $last ? $last->date->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <form action="{{ route('watchlist.horizon', $w) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="horizon" onchange="this.form.submit()"
                                                class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                                <option value="short" {{ $w->horizon=='short'?'selected':'' }}>Corto</option>
                                                <option value="medium" {{ $w->horizon=='medium'?'selected':'' }}>Medio</option>
                                                <option value="long" {{ $w->horizon=='long'?'selected':'' }}>Largo</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-3 text-right">
                                        <form action="{{ route('watchlist.toggle', $inst) }}" method="POST" onsubmit="return confirm('¿Quitar de tu watchlist?')">
                                            @csrf
                                            <button class="px-3 py-2 rounded-xl bg-rose-600 text-white hover:bg-rose-700">
                                                Quitar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Últimas señales --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <h2 class="font-semibold mb-3">Últimas señales (demo)</h2>

            @if($signals->count() == 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Todavía no hay señales. En el próximo paso generamos señales automáticas (SMA/EMA/RSI).
                </p>
            @else
                <div class="space-y-2">
                    @foreach($signals as $s)
                        <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $s->instrument->symbol }} · {{ $s->date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->reason }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $s->action=='buy' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : '' }}
                                    {{ $s->action=='sell' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' : '' }}
                                    {{ $s->action=='hold' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' : '' }}
                                ">
                                    {{ strtoupper($s->action) }}
                                </span>
                                <span class="text-sm font-semibold">{{ $s->score }}/100</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
