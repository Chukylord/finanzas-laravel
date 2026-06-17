@props(['title', 'rows', 'empty', 'money'])

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h2 class="font-semibold">{{ $title }}</h2>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $rows->count() }} items</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="text-left font-medium px-4 sm:px-6 py-3">Categoría</th>
                    <th class="text-left font-medium px-4 sm:px-6 py-3">Subcategoría</th>
                    <th class="text-right font-medium px-4 sm:px-6 py-3">Total</th>
                    <th class="text-right font-medium px-4 sm:px-6 py-3">Cantidad</th>
                    <th class="text-right font-medium px-4 sm:px-6 py-3">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                        <td class="px-4 sm:px-6 py-3">{{ $row['category'] }}</td>
                        <td class="px-4 sm:px-6 py-3 font-medium">{{ $row['subcategory'] }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right font-semibold">{{ $money($row['total']) }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right">{{ $row['count'] }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right">{{ number_format($row['percentage'], 1, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 sm:px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            {{ $empty }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
