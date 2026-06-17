<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Subcategorías</span>

            <a href="{{ route('subcategories.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Nueva subcategoría
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">

        @if(session('ok'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        @if(session('error'))
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('subcategories.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[180px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Tipo</label>
                    <select name="type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todos</option>
                        <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Ingresos</option>
                        <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Egresos</option>
                    </select>
                </div>

                <div class="min-w-[260px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Categoría</label>
                    <select name="category_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    data-type="{{ $category->type }}"
                                    {{ (string)$categoryId === (string)$category->id ? 'selected' : '' }}>
                                {{ $category->type === 'income' ? 'Ingreso' : 'Egreso' }} · {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Filtrar
                </button>

                <a href="{{ route('subcategories.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Limpiar
                </a>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Listado</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Las subcategorías permiten clasificar mejor ingresos y egresos.
                    </p>
                </div>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $subcategories->count() }} items
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Subcategoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Tipo</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Estado</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-56">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($subcategories as $sub)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="font-medium">{{ $sub->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Creada: {{ $sub->created_at?->format('d/m/Y') }}
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $sub->category?->name ?? '—' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    @if($sub->category?->type === 'income')
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Ingreso
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
                                            Egreso
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $sub->active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                        {{ $sub->active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('subcategories.edit', $sub) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Editar
                                        </a>

                                        <form action="{{ route('subcategories.destroy', $sub) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar subcategoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Todavía no tenés subcategorías cargadas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const typeSelect = document.querySelector('[name="type"]');
        const categorySelect = document.querySelector('[name="category_id"]');

        function filterCategoriesByType() {
            if (!typeSelect || !categorySelect) return;

            const type = typeSelect.value;

            Array.from(categorySelect.options).forEach(option => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden = type !== '' && option.dataset.type !== type;
            });

            const selected = categorySelect.options[categorySelect.selectedIndex];
            if (selected && selected.hidden) {
                categorySelect.value = '';
            }
        }

        typeSelect?.addEventListener('change', filterCategoriesByType);
        filterCategoriesByType();
    </script>
</x-app-layout>