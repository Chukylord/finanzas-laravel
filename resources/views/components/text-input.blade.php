@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm
                    placeholder:text-slate-400 shadow-sm
                    focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15
                    transition'
    ]) }}
/>
