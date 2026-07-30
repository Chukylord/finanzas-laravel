<x-app-layout>
    <x-slot name="header">
        <span class="text-lg">Editar tipo de cambio</span>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-4">
        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-900">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div>
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Cotización cargada</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Modificá la fecha o el valor manual USD/ARS.
                </p>
            </div>

            <form method="POST" action="{{ route('exchange-rates.update', $rate) }}" class="mt-5 space-y-5">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Fecha"
                    name="date"
                    type="date"
                    :value="$rate->date->toDateString()"
                    required
                />

                <x-form.input
                    label="USD/ARS"
                    name="usd_ars"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    max="99999999.9999"
                    :value="$rate->usd_ars"
                    required
                />

                <div class="flex justify-end gap-3">
                    <a href="{{ route('exchange-rates.index') }}"
                       class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                        Cancelar
                    </a>
                    <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black text-sm font-semibold">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
