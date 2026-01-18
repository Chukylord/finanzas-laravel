<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900">
<div x-data="{ sidebarOpen:false }" class="min-h-screen flex">

    {{-- Overlay (solo mobile) --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"
        @click="sidebarOpen=false"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed z-50 inset-y-0 left-0 w-72 bg-white border-r border-slate-200
               transform transition-transform duration-200 lg:translate-x-0 lg:static"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-16 px-5 flex items-center justify-between border-b border-slate-200">
            <div class="font-bold text-lg">Finanzas</div>

            <button class="lg:hidden p-2 rounded-md hover:bg-slate-100"
                    @click="sidebarOpen=false" aria-label="Cerrar menú">
                ✕
            </button>
        </div>

        @include('partials.sidebar')
    </aside>

    {{-- Main --}}
    <div class="flex-1 lg:pl-0">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
            <div class="h-16 px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 rounded-md hover:bg-slate-100"
                            @click="sidebarOpen=true" aria-label="Abrir menú">
                        ☰
                    </button>

                    <div class="font-semibold">
                        {{ $header ?? 'Dashboard' }}
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-600 hidden sm:block">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
