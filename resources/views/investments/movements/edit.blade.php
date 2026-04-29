<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Editar movimiento</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Cuenta: <span class="font-semibold">{{ $investment->name }}</span>
                </p>
            </div>
            <a href="{{ route('investments.movements.index', $investment) }}" class="text-sm font-medium hover:underline">
                Volver
            </a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('movements.update', $movement) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Fecha"
                    name="date"
                    type="date"
                    :value="old('date', \Carbon\Carbon::parse($movement->date)->toDateString())"
                    required
                />

                <x-form.select
                    label="Tipo"
                    name="type"
                    :options="[
                        'deposit' => 'Depósito',
                        'withdraw' => 'Retiro',
                        'profit' => 'Ganancia',
                        'loss' => 'Pérdida',
                        'fee' => 'Comisión',
                        'adjust' => 'Ajuste',
                    ]"
                    :selected="old('type', $movement->type)"
                    placeholder="Seleccioná tipo"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.select
                        label="Moneda"
                        name="currency"
                        :options="['ARS' => 'ARS', 'USD' => 'USD']"
                        :selected="old('currency', $movement->currency)"
                        required
                    />

                    <x-form.input
                        label="Monto"
                        name="amount"
                        type="number"
                        step="0.01"
                        :value="old('amount', $movement->amount)"
                        placeholder="Ej: 150000"
                        required
                    />
                </div>

                <x-form.input
                    label="Descripción (opcional)"
                    name="description"
                    :value="old('description', $movement->description)"
                    placeholder="Ej: Ahorro Enero / Transferencia / Comisión"
                />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('investments.movements.index', $investment) }}"
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