<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'App Finanzas') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex">

    {{-- Columna izquierda (branding) --}}
    <div class="hidden lg:flex lg:w-1/2 p-10 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative z-10 text-white flex flex-col justify-between w-full">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                        <span class="text-lg font-bold">₳</span>
                    </div>
                    <div>
                        <div class="text-xl font-semibold">App Finanzas</div>
                        <div class="text-white/70 text-sm">Control completo de tu dinero</div>
                    </div>
                </div>

                <div class="mt-12 space-y-4">
                    <h1 class="text-3xl font-semibold leading-tight">
                        Tu panel financiero, <br>simple y ordenado.
                    </h1>
                    <p class="text-white/70 max-w-md">
                        Ingresos, egresos, recurrentes, cuotas y vencimientos en un solo lugar.
                    </p>
                </div>
            </div>

            <div class="text-white/60 text-sm">
                © {{ date('Y') }} App Finanzas
            </div>
        </div>
    </div>

    {{-- Columna derecha (formulario) --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-6 border border-gray-100 dark:border-gray-800">
                <div class="mb-6">
                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $title ?? 'Bienvenido' }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $subtitle ?? 'Ingresá para continuar' }}
                    </div>
                </div>

                {{ $slot }}
            </div>

            <div class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                Hecho con Laravel + Tailwind
            </div>
        </div>
    </div>
</div>
</body>
</html>
