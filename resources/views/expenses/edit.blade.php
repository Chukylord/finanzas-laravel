<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar egreso</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Actualizá datos del gasto.</p>
            </div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.select
                    label="Categoría"
                    name="category_id"
                    :options="$categories->pluck('name','id')->toArray()"
                    :selected="$expense->category_id"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Fecha" name="date" type="date" :value="$expense->date" required />
                    <x-form.input label="Monto" name="amount" type="number" step="0.01" :value="$expense->amount" required />
                </div>

                <x-form.input
                    label="Detalle (opcional)"
                    name="description"
                    :value="$expense->description"
                    placeholder="Ej: Supermercado / Combustible"
                />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('expenses.index') }}"
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
