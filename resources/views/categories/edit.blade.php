<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar categoría</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Actualizá el nombre o el tipo.</p>
            </div>
            <a href="{{ route('categories.index') }}"
               class="text-sm font-medium text-gray-700 dark:text-gray-200 hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('categories.update', $category) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Nombre"
                    name="name"
                    :value="$category->name"
                    placeholder="Ej: Sueldo, Comida, Servicios"
                    required
                />

                <x-form.select
                    label="Tipo"
                    name="type"
                    :options="['income' => 'Ingreso', 'expense' => 'Egreso']"
                    :selected="$category->type"
                    required
                />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('categories.index') }}"
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
