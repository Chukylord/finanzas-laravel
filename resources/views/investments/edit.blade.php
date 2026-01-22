<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar cuenta</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Podés marcarla como inactiva sin perder historial.</p>
            </div>
            <a href="{{ route('investments.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('investments.update', $account) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Nombre"
                    name="name"
                    :value="$account->name"
                    required
                />

                <x-form.select
                    label="Moneda por defecto"
                    name="default_currency"
                    :options="['ARS' => 'ARS', 'USD' => 'USD']"
                    :selected="$account->default_currency"
                    required
                />

                <x-form.textarea
                    label="Notas (opcional)"
                    name="notes"
                    :value="$account->notes"
                    placeholder="Ej: objetivo, notas, etc."
                />

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="active" class="rounded"
                           {{ old('active', $account->active) ? 'checked' : '' }}>
                    <label class="text-sm text-gray-700 dark:text-gray-200">Activa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('investments.index') }}"
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
