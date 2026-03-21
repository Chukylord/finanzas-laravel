<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Mercados</span>
            <div class="flex gap-2">
                <a href="{{ route('instruments.index') }}"
                   class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                    Instrumentos
                </a>

                @if (Route::has('signal-rules.index'))
                    <a href="{{ route('signal-rules.index') }}"
                       class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                        Reglas
                    </a>
                @endif
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
                                <th class="py-3 pr-4">Var.</th>
                                <th class="py-3 pr-4">Actualizado</th>
                                <th class="py-3 pr-4">Horizonte</th>
                                <th class="py-3 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($watchlist as $w)
                                @php
                                    $inst = $w->instrument;

                                    // Si por algún motivo falta relación, evitamos romper
                                    if (!$inst) continue;

                                    $last = $lastPrices[$inst->id] ?? null;

                                    $chg = $last?->change_pct;
                                    $ccy = $last?->currency;
                                    $fAt = $last?->fetched_at;

                                    $badgeClass = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';
                                    if (!is_null($chg)) {
                                        if ($chg > 0) $badgeClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200';
                                        if ($chg < 0) $badgeClass = 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200';
                                    }

                                    $moneySymbol = '$';
                                    if (is_string($ccy)) {
                                        $c = strtolower($ccy);
                                        if (str_contains($c, 'dolar') || str_contains($c, 'usd')) $moneySymbol = 'US$';
                                    }

                                    $updatedText = '—';
                                    if ($last) {
                                        if ($fAt instanceof \Carbon\CarbonInterface) {
                                            $updatedText = $fAt->format('d/m/Y H:i');
                                        } else {
                                            try { $updatedText = \Carbon\Carbon::parse($fAt)->format('d/m/Y H:i'); }
                                            catch (\Throwable $e) { $updatedText = $last->date?->format('d/m/Y') ?? '—'; }
                                        }
                                    }
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                    <td class="py-3 pr-4 font-semibold">{{ $inst->symbol }}</td>

                                    <td class="py-3 pr-4">
                                        <div class="font-medium">{{ $inst->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ strtoupper($inst->market ?? '—') }}
                                            @if($last && $ccy) · {{ $ccy }} @endif
                                        </div>
                                    </td>

                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-xs">
                                            {{ strtoupper($inst->type) }}
                                        </span>
                                    </td>

                                    <td class="py-3 pr-4">
                                        @if($last)
                                            <span class="font-semibold">
                                                {{ $moneySymbol }}{{ number_format((float)$last->close, 2, ',', '.') }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="py-3 pr-4">
                                        @if(is_null($chg))
                                            <span class="text-gray-500 dark:text-gray-400">—</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                {{ $chg > 0 ? '▲' : ($chg < 0 ? '▼' : '•') }}
                                                {{ number_format((float)$chg, 2, ',', '.') }}%
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">
                                        {{ $updatedText }}
                                    </td>

                                    <td class="py-3 pr-4">
                                        <form action="{{ route('watchlist.horizon', $w) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="horizon" onchange="this.form.submit()"
                                                    class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                                <option value="short"  {{ $w->horizon=='short'?'selected':'' }}>Corto</option>
                                                <option value="medium" {{ $w->horizon=='medium'?'selected':'' }}>Medio</option>
                                                <option value="long"   {{ $w->horizon=='long'?'selected':'' }}>Largo</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td class="py-3 text-right">
                                        <form action="{{ route('watchlist.toggle', $inst) }}" method="POST"
                                              onsubmit="return confirm('¿Quitar de tu watchlist?')">
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

        @if(!empty($marketWarnings))
            <div class="px-4 py-3 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-sm space-y-1">
                @foreach($marketWarnings as $warning)
                    <div>{{ $warning }}</div>
                @endforeach
            </div>
        @endif

        {{-- Recomendaciones --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Recomendaciones (watchlist)</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Short / Long</span>
            </div>

            @if(empty($recommendations))
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay datos suficientes para recomendar. Agregá instrumentos y corré la sincronización de precios.
                </p>
            @else
                <div class="space-y-2">
                    @foreach($recommendations as $rec)
                        <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $rec['instrument']->symbol }} · {{ strtoupper($rec['horizon']) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $rec['reason'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Precio: ${{ number_format((float)$rec['price'], 2, ',', '.') }}
                                    · Variación: {{ number_format((float)$rec['change_pct'], 2, ',', '.') }}%
                                    · Fuente: {{ $rec['source'] }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $rec['action']=='buy' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : '' }}
                                    {{ $rec['action']=='sell' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' : '' }}
                                    {{ $rec['action']=='hold' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' : '' }}
                                ">
                                    {{ $rec['action']=='buy' ? 'COMPRAR' : ($rec['action']=='sell' ? 'VENDER' : 'MANTENER') }}
                                </span>
                                <span class="text-sm font-semibold">{{ (int)$rec['score'] }}/100</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Universo CEDEAR (Top señales) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Universo CEDEAR (Top señales)</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Según tus reglas</span>
            </div>

            @php
                // Si $signals viene vacío, mostramos mensaje. Si viene, mostramos top por horizon.
                $short = $signals?->where('horizon','short')->sortByDesc('score')->take(8) ?? collect();
                $medium = $signals?->where('horizon','medium')->sortByDesc('score')->take(8) ?? collect();
                $long = $signals?->where('horizon','long')->sortByDesc('score')->take(8) ?? collect();

                $hasAny = ($signals && $signals->count() > 0);
            @endphp

            @if(!$hasAny)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Todavía no hay señales generadas. Cuando tengas reglas activas y snapshots, corré:
                    <span class="font-mono">php artisan signals:generate --user={{ auth()->id() }}</span>
                </p>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @foreach([['short','Corto',$short,'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200'],
                              ['medium','Medio',$medium,'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200'],
                              ['long','Largo',$long,'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200']] as $block)
                        @php [$hKey,$hLabel,$list,$badge] = $block; @endphp

                        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold">{{ $hLabel }}</div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">
                                    {{ strtoupper($hKey) }}
                                </span>
                            </div>

                            @if($list->count() === 0)
                                <div class="text-sm text-gray-500 dark:text-gray-400">Sin señales para este horizonte.</div>
                            @else
                                <div class="space-y-2">
                                    @foreach($list as $s)
                                        @php
                                            $actionClass = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';
                                            if ($s->action === 'buy')  $actionClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200';
                                            if ($s->action === 'sell') $actionClass = 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200';
                                        @endphp

                                        <div class="flex items-center justify-between p-2 rounded-xl border border-gray-100 dark:border-gray-800">
                                            <div class="min-w-0">
                                                <div class="font-medium truncate">{{ $s->instrument?->symbol ?? '—' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $s->reason }}</div>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="px-2 py-1 rounded-full text-xs {{ $actionClass }}">
                                                    {{ strtoupper($s->action) }}
                                                </span>
                                                <span class="text-sm font-semibold">{{ (int)$s->score }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Top BUY (hoy)</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Score</span>
                </div>

                @if(($topBuy ?? collect())->count() === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay señales BUY hoy.</p>
                @else
                    <div class="space-y-2">
                        @foreach($topBuy as $s)
                            <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                <div>
                                    <div class="font-medium">{{ $s->instrument?->symbol ?? '—' }} · {{ strtoupper($s->horizon) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->reason }}</div>
                                </div>
                                <div class="font-semibold">{{ $s->score }}/100</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Top SELL (hoy)</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Score</span>
                </div>

                @if(($topSell ?? collect())->count() === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay señales SELL hoy.</p>
                @else
                    <div class="space-y-2">
                        @foreach($topSell as $s)
                            <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                <div>
                                    <div class="font-medium">{{ $s->instrument?->symbol ?? '—' }} · {{ strtoupper($s->horizon) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->reason }}</div>
                                </div>
                                <div class="font-semibold">{{ $s->score }}/100</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Últimas señales (timeline) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-5">
            <h2 class="font-semibold mb-3">Últimas señales</h2>

            @if($signals->count() == 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Todavía no hay señales.
                </p>
            @else
                <div class="space-y-2">
                    @foreach($signals->take(10) as $s)
                        @php
                            $actionClass = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';
                            if ($s->action === 'buy')  $actionClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200';
                            if ($s->action === 'sell') $actionClass = 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200';
                        @endphp

                        <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="font-medium">
                                    {{ $s->instrument?->symbol ?? '—' }}
                                    · {{ strtoupper($s->horizon) }}
                                    · {{ $s->date->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $s->reason }}</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-1 rounded-full text-xs {{ $actionClass }}">
                                    {{ strtoupper($s->action) }}
                                </span>
                                <span class="text-sm font-semibold">{{ (int)$s->score }}/100</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
