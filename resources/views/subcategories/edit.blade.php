<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Editar subcategoría</span>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar subcategoría</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Modificá el nombre, categoría o estado.
                </p>
            </div>

            <a href="{{ route('subcategories.index') }}" class="text-sm font-medium hover:underline">
                Volver
            </a>
        </div>

        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('subcategories.update', $subcategory) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Categoría
                    </label>

                    <select name="category_id"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"
                            required>
                        <option value="">Elegí una categoría</option>

                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string)old('category_id', $subcategory->category_id) === (string)$category->id ? 'selected' : '' }}>
                                {{ $category->type === 'income' ? 'Ingreso' : 'Egreso' }} · {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-form.input
                    label="Nombre"
                    name="name"
                    :value="old('name', $subcategory->name)"
                    placeholder="Ej: Supermercado / Nafta / Sueldo fijo"
                    required
                />

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="active" value="1" class="rounded"
                           {{ old('active', $subcategory->active) ? 'checked' : '' }}>
                    <label class="text-sm text-gray-700 dark:text-gray-200">Activa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('subcategories.index') }}"
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