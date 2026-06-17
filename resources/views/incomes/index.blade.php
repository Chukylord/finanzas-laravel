<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Ingresos</span>
            <a href="{{ route('incomes.create') }}" class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                + Cargar ingreso
            </a>
        </div>
    </x-slot>

    @php
        $months = [
            1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
            7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
        ];
        $totalIncomeList = $incomes->sum('amount');
        $filterQuery = array_filter([
            'month' => $month,
            'year' => $year,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

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
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form method="GET" action="{{ route('incomes.index') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Mes</label>
                    <select name="month" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>
                                {{ $months[$m] }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Año</label>
                    <input type="number" name="year"
                           class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 w-28"
                           value="{{ $year }}">
                </div>

                <div class="min-w-[220px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Categoría</label>
                    <select name="category_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todas</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[240px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Subcategoría</label>
                    <select name="subcategory_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Todas</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}"
                                    data-category="{{ $sub->category_id }}"
                                    {{ (string)$subcategoryId === (string)$sub->id ? 'selected' : '' }}>
                                {{ $sub->category?->name }} · {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Filtrar
                </button>

                <a href="{{ route('incomes.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                    Limpiar
                </a>

                <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                    Total: <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalIncomeList, 2, ',', '.') }}</span>
                    <span class="mx-2">·</span>
                    {{ $months[(int)$month] ?? $month }}/{{ $year }}
                </div>
            </form>
        </div>

        {{-- Edicion masiva --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4">
            <form id="bulk-income-form"
                  method="POST"
                  action="{{ route('incomes.bulk-update', $filterQuery) }}"
                  class="flex flex-wrap gap-3 items-end"
                  onsubmit="return confirm('Actualizar los ingresos seleccionados?')">
                @csrf

                <div class="min-w-[220px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Categoria masiva</label>
                    <select name="bulk_category_id" data-bulk-category="incomes" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">No cambiar</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[240px]">
                    <label class="block text-sm mb-1 text-gray-600 dark:text-gray-300">Subcategoria masiva</label>
                    <select name="bulk_subcategory_id" data-bulk-subcategory="incomes" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">No cambiar</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" data-category="{{ $sub->category_id }}">
                                {{ $sub->category?->name }} · {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm">
                    <input type="checkbox" name="clear_subcategory" value="1" data-clear-subcategory="incomes"
                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                    <span>Limpiar subcategoria</span>
                </label>

                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Aplicar a seleccionados
                </button>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Solo afecta los ingresos marcados en la tabla.
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="text-left font-medium px-4 sm:px-6 py-3 w-12">
                                <input type="checkbox" data-select-all="incomes"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                       aria-label="Seleccionar todos los ingresos visibles">
                            </th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Fecha</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Subcategoría</th>
                            <th class="text-left font-medium px-4 sm:px-6 py-3">Detalle</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3">Monto</th>
                            <th class="text-right font-medium px-4 sm:px-6 py-3 w-44">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($incomes as $i)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 sm:px-6 py-3">
                                    <input type="checkbox"
                                           name="selected_ids[]"
                                           value="{{ $i->id }}"
                                           form="bulk-income-form"
                                           data-select-row="incomes"
                                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                           aria-label="Seleccionar ingreso {{ $i->id }}">
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ \Carbon\Carbon::parse($i->date)->format('d/m/Y') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $i->category?->name ?? '-' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3">
                                    {{ $i->subcategory?->name ?? '—' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $i->description ?: '-' }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right font-semibold">
                                    ${{ number_format($i->amount, 2, ',', '.') }}
                                </td>

                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="inline-flex gap-3 justify-end">
                                        <a class="text-blue-700 hover:underline"
                                           href="{{ route('incomes.edit', $i) }}?month={{ $month }}&year={{ $year }}">
                                            Editar
                                        </a>

                                        <form action="{{ route('incomes.destroy', $i) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar ingreso?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay ingresos para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse

                        @if($incomes->count() > 0)
                            <tr class="bg-gray-50 dark:bg-gray-800/30">
                                <td class="px-4 sm:px-6 py-3 font-semibold" colspan="5">TOTAL</td>
                                <td class="px-4 sm:px-6 py-3 text-right font-bold">
                                    ${{ number_format($totalIncomeList, 2, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const catFilter = document.querySelector('[name="category_id"]');
        const subFilter = document.querySelector('[name="subcategory_id"]');

        function filterSubcategoryFilter() {
            if (!catFilter || !subFilter) return;

            const catId = catFilter.value;

            Array.from(subFilter.options).forEach(option => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden = catId !== '' && option.dataset.category !== catId;
            });

            const selected = subFilter.options[subFilter.selectedIndex];
            if (selected && selected.hidden) {
                subFilter.value = '';
            }
        }

        function setupBulkSelection(scope) {
            const selectAll = document.querySelector(`[data-select-all="${scope}"]`);
            const rows = Array.from(document.querySelectorAll(`[data-select-row="${scope}"]`));

            if (!selectAll) return;

            selectAll.disabled = rows.length === 0;

            function syncSelectAll() {
                const checked = rows.filter(row => row.checked).length;
                selectAll.checked = rows.length > 0 && checked === rows.length;
                selectAll.indeterminate = checked > 0 && checked < rows.length;
            }

            selectAll.addEventListener('change', () => {
                rows.forEach(row => {
                    row.checked = selectAll.checked;
                });
                syncSelectAll();
            });

            rows.forEach(row => row.addEventListener('change', syncSelectAll));
            syncSelectAll();
        }

        function setupBulkSubcategories(scope) {
            const bulkCategory = document.querySelector(`[data-bulk-category="${scope}"]`);
            const bulkSubcategory = document.querySelector(`[data-bulk-subcategory="${scope}"]`);
            const clearSubcategory = document.querySelector(`[data-clear-subcategory="${scope}"]`);

            if (!bulkCategory || !bulkSubcategory) return;

            function filterBulkSubcategories() {
                const categoryId = bulkCategory.value;

                Array.from(bulkSubcategory.options).forEach(option => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = categoryId === '' || option.dataset.category !== categoryId;
                });

                const selected = bulkSubcategory.options[bulkSubcategory.selectedIndex];
                if (selected && selected.hidden) {
                    bulkSubcategory.value = '';
                }
            }

            function syncClearState() {
                const shouldDisable = clearSubcategory?.checked ?? false;
                bulkSubcategory.disabled = shouldDisable;

                if (shouldDisable) {
                    bulkSubcategory.value = '';
                }
            }

            bulkCategory.addEventListener('change', filterBulkSubcategories);
            clearSubcategory?.addEventListener('change', syncClearState);
            filterBulkSubcategories();
            syncClearState();
        }

        catFilter?.addEventListener('change', filterSubcategoryFilter);
        filterSubcategoryFilter();
        setupBulkSelection('incomes');
        setupBulkSubcategories('incomes');
    </script>
</x-app-layout>
