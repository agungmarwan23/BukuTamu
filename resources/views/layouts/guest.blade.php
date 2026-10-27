<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Buku Tamu BPS Inhil') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased bg-gradient-to-br from-slate-100 via-blue-50/40 to-slate-200 min-h-screen">
        <div class="min-h-screen flex flex-col sm:justify-center items-center py-8 px-4 sm:px-6">
            <div class="mb-6">
                <a href="/">
                    <x-application-logo />
                </a>
            </div>

            <div class="w-full sm:max-w-md bg-white/95 backdrop-blur-sm px-8 py-8 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-200/80">
                {{ $slot }}
            </div>

            <div class="mt-8 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir
            </div>
        </div>
    </body>
</html>
