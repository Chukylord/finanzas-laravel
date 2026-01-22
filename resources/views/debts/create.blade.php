<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Nueva deuda</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    El próximo vencimiento se calcula automáticamente con el día del mes.
                </p>
            </div>
            <a href="{{ route('debts.index') }}" class="text-sm font-medium hover:underline">Volver</a>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form method="POST" action="{{ route('debts.store') }}" class="p-6 space-y-5">
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
                    placeholder="Ej: Celular / Préstamo banco"
                    required
                />

                <x-form.select
                    label="Tipo"
                    name="type"
                    :options="['loan' => 'Préstamo', 'card_installment' => 'Tarjeta (cuotas)']"
                    placeholder="Seleccioná tipo"
                    required
                />

                {{-- Totales + pagadas --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.input
                        label="Monto total"
                        name="total_amount"
                        type="number"
                        step="0.01"
                        placeholder="Ej: 1200000"
                        required
                    />

                    <x-form.input
                        label="Cuotas totales"
                        name="installments_total"
                        type="number"
                        placeholder="Ej: 12"
                        required
                        min="1"
                        max="600"
                    />

                    {{-- ✅ Cuotas pagadas (default 0) --}}
                    <x-form.input
                        label="Cuotas pagadas"
                        name="installments_paid"
                        type="number"
                        placeholder="0"
                        min="0"
                        max="600"
                        :value="old('installments_paid', 0)"
                    />
                </div>

                {{-- Monto cuota + Día vencimiento --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- ✅ Campo custom para incluir botón AUTO --}}
                    <div>
                        <label for="installment_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Monto cuota
                        </label>

                        <div class="mt-1 flex gap-2">
                            <input
                                id="installment_amount"
                                name="installment_amount"
                                type="number"
                                step="0.01"
                                required
                                placeholder="Ej: 100000"
                                value="{{ old('installment_amount') }}"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />

                            <button type="button" id="autoBtn"
                                class="shrink-0 px-3 py-2 rounded-xl text-sm font-semibold border transition
                                       border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100
                                       dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                                Auto
                            </button>
                        </div>

                        @error('installment_amount')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        {{-- Mini resumen --}}
                        <p id="installmentHint" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>

                    <x-form.input
                        label="Día vencimiento (1..28)"
                        name="day_of_month"
                        type="number"
                        placeholder="Ej: 10"
                        required
                        min="1"
                        max="28"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('debts.index') }}"
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

    <script>
        (function () {
            const totalEl = document.querySelector('input[name="total_amount"]');
            const totalCuotasEl = document.querySelector('input[name="installments_total"]');
            const pagadasEl = document.querySelector('input[name="installments_paid"]');
            const cuotaEl = document.getElementById('installment_amount');
            const btn = document.getElementById('autoBtn');
            const hint = document.getElementById('installmentHint');

            let autoMode = true;

            function toNum(el) {
                const v = parseFloat((el?.value ?? '').toString().replace(',', '.'));
                return isNaN(v) ? null : v;
            }

            function fmt(n) {
                try {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
                } catch (e) {
                    return (Math.round(n * 100) / 100).toString();
                }
            }

            function setBtnState() {
                // Verde cuando está en auto, gris cuando está manual
                if (autoMode) {
                    btn.className =
                        "shrink-0 px-3 py-2 rounded-xl text-sm font-semibold border transition " +
                        "border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 " +
                        "dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200";
                    btn.textContent = "Auto ✓";
                } else {
                    btn.className =
                        "shrink-0 px-3 py-2 rounded-xl text-sm font-semibold border transition " +
                        "border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 " +
                        "dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200";
                    btn.textContent = "Auto";
                }
            }

            function updateHint() {
                const total = toNum(totalEl);
                const cuotasTotal = toNum(totalCuotasEl);
                const pagadas = toNum(pagadasEl) ?? 0;
                const cuota = toNum(cuotaEl);

                if (!total || !cuotasTotal || cuotasTotal <= 0) {
                    hint.textContent = "";
                    return;
                }

                const pag = Math.max(0, Math.min(parseInt(pagadas, 10) || 0, parseInt(cuotasTotal, 10) || 0));
                const restan = Math.max(0, (parseInt(cuotasTotal, 10) || 0) - pag);

                // Saldo estimado = total - pagadas * cuota (si cuota existe)
                let saldoTxt = "";
                if (cuota && cuota >= 0) {
                    const saldo = Math.max(0, total - (pag * cuota));
                    saldoTxt = ` · Saldo estimado: $${fmt(saldo)}`;
                }

                hint.textContent =
                    `Pagadas: ${pag}/${parseInt(cuotasTotal, 10)} · Restan: ${restan}` + saldoTxt;
            }

            function recalcCuotaIfAuto() {
                if (!autoMode) {
                    updateHint();
                    return;
                }

                const total = toNum(totalEl);
                const cuotas = toNum(totalCuotasEl);

                if (!total || !cuotas || cuotas <= 0) {
                    updateHint();
                    return;
                }

                const calc = total / cuotas;
                cuotaEl.value = (Math.round(calc * 100) / 100).toFixed(2);

                updateHint();
            }

            // Inicial: si ya venía algo escrito en cuota (old), lo consideramos MANUAL
            if (cuotaEl.value && cuotaEl.value.toString().trim() !== "") {
                autoMode = false;
            }
            setBtnState();
            updateHint();

            // Eventos
            totalEl.addEventListener('input', recalcCuotaIfAuto);
            totalCuotasEl.addEventListener('input', recalcCuotaIfAuto);
            pagadasEl.addEventListener('input', updateHint);

            // Si el usuario toca cuota manualmente => auto OFF (gris)
            cuotaEl.addEventListener('input', function () {
                if (autoMode) {
                    autoMode = false;
                    setBtnState();
                }
                updateHint();
            });

            // Botón Auto: vuelve a activar y recalcula
            btn.addEventListener('click', function () {
                autoMode = true;
                setBtnState();
                recalcCuotaIfAuto();
                cuotaEl.focus();
            });
        })();
    </script>
</x-app-layout>
