@props(['title' => '', 'value' => '', 'hint' => '', 'tone' => 'indigo'])

@php
$tones = [
    'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
    'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
];
$badge = $tones[$tone] ?? $tones['indigo'];
@endphp

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-600">{{ $title }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $value }}</p>
            @if($hint)
                <p class="mt-2 text-sm text-slate-500">{{ $hint }}</p>
            @endif
        </div>

        <div class="shrink-0">
            <div class="inline-flex items-center justify-center w-11 h-11 rounded-2xl ring-1 {{ $badge }}">
                {{ $icon ?? '★' }}
            </div>
        </div>
    </div>
</div>
