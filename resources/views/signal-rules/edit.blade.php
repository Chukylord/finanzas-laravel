<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Editar regla</span>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar regla</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Podés activar/desactivar y ajustar parámetros.</p>
            </div>
            <a href="{{ route('signal-rules.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('signal-rules.update', $rule) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Nombre"
                    name="name"
                    :value="old('name', $rule->name)"
                    required
                />

                <x-form.select
                    label="Horizonte"
                    name="horizon"
                    :options="['short'=>'Corto', 'medium'=>'Medio', 'long'=>'Largo']"
                    :selected="old('horizon', $rule->horizon)"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Fast MA"
                        name="fast_ma"
                        type="number"
                        :value="old('fast_ma', $rule->fast_ma)"
                        required min="2" max="300"
                    />
                    <x-form.input
                        label="Slow MA"
                        name="slow_ma"
                        type="number"
                        :value="old('slow_ma', $rule->slow_ma)"
                        required min="2" max="600"
                    />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    @php $checked = old('active', $rule->active) ? true : false; @endphp
                    <input type="checkbox" name="active" value="1" class="rounded" {{ $checked ? 'checked' : '' }}>
                    <label class="text-sm text-gray-700 dark:text-gray-200">Activa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('signal-rules.index') }}"
                       class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Cancelar
                    </a>
                    <button class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
