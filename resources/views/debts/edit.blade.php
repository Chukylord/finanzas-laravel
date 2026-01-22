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

                    {{-- Monto cuota + botón --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Monto cuota
                        </label>

                        <div class="flex gap-2">
                            <input
                                id="installment_amount"
                                name="installment_amount"
                                type="number"
                                step="0.01"
                                value="{{ old('installment_amount', $debt->installment_amount) }}"
                                required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"
                            />

                            <button
                                type="button"
                                id="btn_recalc_cuota"
                                class="shrink-0 px-3 py-2 rounded-xl text-sm font-semibold border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                title="Recalcular Monto cuota"
                            >
                                Recalcular
                            </button>
                        </div>

                        @error('installment_amount')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Se calcula como <span class="font-medium">Monto total / Cuotas</span>.
                        </p>
                    </div>
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

    <script>
        (function () {
            const totalEl = document.querySelector('input[name="total_amount"]');
            const cuotasEl = document.querySelector('input[name="installments_total"]');
            const cuotaEl = document.querySelector('input[name="installment_amount"]');
            const btn = document.getElementById('btn_recalc_cuota');

            if (!totalEl || !cuotasEl || !cuotaEl || !btn) return;

            let cuotaTouched = false;

            const toNumber = (v) => {
                if (v === null || v === undefined) return 0;
                const str = String(v).replace(',', '.');
                const n = parseFloat(str);
                return Number.isFinite(n) ? n : 0;
            };

            const round2 = (n) => Math.round(n * 100) / 100;

            const calc = (force = false) => {
                if (!force && cuotaTouched) return;

                const total = toNumber(totalEl.value);
                const cuotas = toNumber(cuotasEl.value);

                if (total > 0 && cuotas > 0) {
                    cuotaEl.value = round2(total / cuotas);
                }
            };

            cuotaEl.addEventListener('input', () => {
                cuotaTouched = true;
            });

            totalEl.addEventListener('input', () => calc(false));
            cuotasEl.addEventListener('input', () => calc(false));

            btn.addEventListener('click', () => {
                cuotaTouched = false;
                calc(true);
                cuotaEl.focus();
            });
        })();
    </script>
</x-app-layout>
