<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'App Finanzas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
<div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-72 hidden lg:flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="p-6 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gray-900 text-white flex items-center justify-center font-bold">
                    ₳
                </div>
                <div>
                    <div class="font-semibold">App Finanzas</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Panel</div>
                </div>
            </div>
        </div>

        <nav class="p-4 space-y-1 text-sm">
            @php
                $link = "flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800";
                $active = "bg-gray-100 dark:bg-gray-800 font-semibold";
            @endphp

            <a class="{{ $link }} {{ request()->routeIs('dashboard') ? $active : '' }}" href="{{ route('dashboard') }}">
                <span>📊</span> <span>Dashboard</span>
            </a>

            <a class="{{ $link }} {{ request()->routeIs('incomes.*') ? $active : '' }}" href="{{ route('incomes.index') }}">
                <span>💰</span> <span>Ingresos</span>
            </a>

            <a class="{{ $link }} {{ request()->routeIs('expenses.*') ? $active : '' }}" href="{{ route('expenses.index') }}">
                <span>💸</span> <span>Egresos</span>
            </a>

            <a class="{{ $link }} {{ request()->routeIs('recurrings.*') ? $active : '' }}" href="{{ route('recurrings.index') }}">
                <span>🔁</span> <span>Recurrentes</span>
            </a>

            <a class="{{ $link }} {{ request()->routeIs('debts.*') ? $active : '' }}" href="{{ route('debts.index') }}">
                <span>🧾</span> <span>Cuotas / Deudas</span>
            </a>

            <a class="{{ $link }} {{ request()->routeIs('categories.*') ? $active : '' }}" href="{{ route('categories.index') }}">
                <span>🏷️</span> <span>Categorías</span>
            </a>
        </nav>

        <div class="mt-auto p-4 border-t border-gray-200 dark:border-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Logueado como
            </div>
            <div class="font-medium truncate">{{ auth()->user()->name }}</div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="font-semibold">
                    {{ $header ?? '' }}
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}"
                       class="px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                        Perfil
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="px-3 py-2 rounded-lg bg-gray-900 text-white hover:bg-black text-sm">
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
