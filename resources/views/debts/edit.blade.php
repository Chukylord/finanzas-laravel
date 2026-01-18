<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar deuda</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Podés corregir cuotas pagadas y el sistema ajusta el estado.</p>
            </div>
            <a href="{{ route('debts.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('debts.update', $debt) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.select
                    label="Categoría (Egreso)"
                    name="category_id"
                    :options="$categories->pluck('name','id')->toArray()"
                    :selected="$debt->category_id"
                    required
                />

                <x-form.input label="Nombre" name="name" :value="$debt->name" required />

                <x-form.select
                    label="Tipo"
                    name="type"
                    :options="['loan' => 'Préstamo', 'card_installment' => 'Tarjeta (cuotas)']"
                    :selected="$debt->type"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Monto total" name="total_amount" type="number" step="0.01" :value="$debt->total_amount" required />
                    <x-form.input label="Cuotas totales" name="installments_total" type="number" :value="$debt->installments_total" required min="1" max="600" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="Cuotas pagadas" name="installments_paid" type="number" :value="$debt->installments_paid" required min="0" max="600" />
                    <x-form.input label="Monto cuota" name="installment_amount" type="number" step="0.01" :value="$debt->installment_amount" required />
                </div>

                <x-form.input
                    label="Día vencimiento (1..28)"
                    name="day_of_month"
                    type="number"
                    :value="$debt->day_of_month"
                    required
                    min="1"
                    max="28"
                />

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="active" class="rounded"
                           {{ old('active', $debt->active) ? 'checked' : '' }}>
                    <label class="text-sm text-gray-700 dark:text-gray-200">Activa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('debts.index') }}"
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
