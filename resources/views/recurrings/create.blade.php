<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Nuevo recurrente</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">La próxima fecha se calcula automáticamente con el día del mes.</p>
            </div>
            <a href="{{ route('recurrings.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('recurrings.store') }}" class="p-6 space-y-5">
                @csrf

                <x-form.select
                    label="Categoría (Egreso)"
                    name="category_id"
                    :options="$categories->pluck('name','id')->toArray()"
                    placeholder="Elegí una categoría"
                    required
                />

                <x-form.input
                    label="Nombre"
                    name="name"
                    placeholder="Ej: Netflix, Alquiler, Internet"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input
                        label="Monto"
                        name="amount"
                        type="number"
                        step="0.01"
                        placeholder="Ej: 17000"
                        required
                    />

                    <x-form.select
                        label="Período"
                        name="period"
                        :options="['monthly' => 'Mensual', 'yearly' => 'Anual']"
                        placeholder="Seleccioná período"
                        required
                    />
                </div>

                <x-form.input
                    label="Día del mes (1..28)"
                    name="day_of_month"
                    type="number"
                    placeholder="Ej: 5"
                    required
                    min="1"
                    max="28"
                />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('recurrings.index') }}"
                       class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Cancelar
                    </a>
                    <button class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
