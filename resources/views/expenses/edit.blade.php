<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar egreso</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Actualizá categoría, subcategoría y datos del gasto.</p>
            </div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.select
                    label="Categoría"
                    name="category_id"
                    :options="$categories->pluck('name','id')->toArray()"
                    :selected="old('category_id', $expense->category_id)"
                    required
                />

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Subcategoría (opcional)
                    </label>
                    <select name="subcategory_id"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Sin subcategoría</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}"
                                    data-category="{{ $sub->category_id }}"
                                    {{ (string)old('subcategory_id', $expense->subcategory_id) === (string)$sub->id ? 'selected' : '' }}>
                                {{ $sub->category?->name }} · {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Podés clasificar egresos antiguos sin perder datos.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Fecha"
                        name="date"
                        type="date"
                        :value="old('date', \Carbon\Carbon::parse($expense->date)->toDateString())"
                        required
                    />

                    <x-form.input
                        label="Monto"
                        name="amount"
                        type="number"
                        step="0.01"
                        :value="old('amount', $expense->amount)"
                        required
                    />
                </div>

                <x-form.input
                    label="Detalle (opcional)"
                    name="description"
                    :value="old('description', $expense->description)"
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

    <script>
        const catSelect = document.querySelector('[name="category_id"]');
        const subSelect = document.querySelector('[name="subcategory_id"]');

        function filterSubcategories() {
            if (!catSelect || !subSelect) return;

            const catId = catSelect.value;

            Array.from(subSelect.options).forEach(option => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden = option.dataset.category !== catId;
            });

            const selected = subSelect.options[subSelect.selectedIndex];
            if (selected && selected.hidden) {
                subSelect.value = '';
            }
        }

        catSelect?.addEventListener('change', filterSubcategories);
        filterSubcategories();
    </script>
</x-app-layout>