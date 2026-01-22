<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg">Nuevo instrumento</span>
            <a href="{{ route('instruments.index') }}" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
        <form method="POST" action="{{ route('instruments.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Símbolo</label>
                    <input name="symbol" value="{{ old('symbol') }}" placeholder="Ej: GGAL, AAPL, BTCUSDT"
                           class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    @error('symbol') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Nombre</label>
                    <input name="name" value="{{ old('name') }}" placeholder="Ej: Grupo Galicia"
                           class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    @error('name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Tipo</label>
                    <select name="type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="accion" {{ old('type')=='accion'?'selected':'' }}>Acción</option>
                        <option value="cedear" {{ old('type')=='cedear'?'selected':'' }}>CEDEAR</option>
                        <option value="fci" {{ old('type')=='fci'?'selected':'' }}>FCI</option>
                        <option value="crypto" {{ old('type')=='crypto'?'selected':'' }}>Crypto</option>
                    </select>
                    @error('type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Mercado</label>
                    <input name="market" value="{{ old('market') }}" placeholder="Ej: BYMA, NASDAQ, CRYPTO"
                           class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    @error('market') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Moneda</label>
                    <select name="currency" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="ARS" {{ old('currency','ARS')=='ARS'?'selected':'' }}>ARS</option>
                        <option value="USD" {{ old('currency')=='USD'?'selected':'' }}>USD</option>
                    </select>
                    @error('currency') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="active" class="rounded" {{ old('active', true) ? 'checked' : '' }}>
                        <span class="text-sm">Activo</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('instruments.index') }}"
                   class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Cancelar
                </a>
                <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-black">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
