<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Buku Tamu BPS Inhil') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white min-h-screen flex flex-col justify-between">
        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-600/30 border border-blue-400/30 backdrop-blur-md flex items-center justify-center font-bold text-amber-400 text-lg shadow-lg">
                    BPS
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-white leading-tight">Buku Tamu Digital & Antrean</h1>
                    <p class="text-xs font-semibold text-amber-400 tracking-wider">BPS KAB. INDRAGIRI HILIR</p>
                </div>
            </div>

            <div>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-md">
                        <span>Buka Dasbor</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium backdrop-blur-sm transition shadow-sm">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Masuk Petugas</span>
                    </a>
                @endauth
            </div>
        </header>

        <!-- Main Hero -->
        <main class="w-full max-w-5xl mx-auto px-6 py-12 flex-grow flex flex-col justify-center items-center text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-400/30 text-blue-300 text-xs font-medium mb-6 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Pelayanan Statistik Terpadu (PST) Modern & Tertib
            </div>

            <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white max-w-3xl leading-tight">
                Selamat Datang di Pelayanan Statistik Terpadu
            </h2>
            <p class="mt-4 text-slate-300 max-w-2xl text-base sm:text-lg">
                Sistem pencatatan buku tamu digital, manajemen nomor antrean terintegrasi layar TV lobi, dan penjadwalan petugas piket BPS Kabupaten Indragiri Hilir.
            </p>

            <!-- Quick Cards -->
            <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-6 w-full text-left">
                <!-- Kios Tamu -->
                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Kios Lobi Kantor</h3>
                    <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                        Pengisian buku tamu mandiri di tablet/PC lobi lengkap dengan pengambilan foto dan cetak tiket antrean (Fase 2 & 3).
                    </p>
                </div>

                <!-- Form Online -->
                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-blue-400 transition">Permintaan Online</h3>
                    <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                        Formulir layanan statistik online bagi pengunjung dari luar kantor dengan bukti tiket elektronik (Fase 2).
                    </p>
                </div>

                <!-- Layar Antrean -->
                <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md hover:bg-white/10 transition group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white group-hover:text-emerald-400 transition">Layar TV Lobi</h3>
                    <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                        Tampilan antrean nomor besar, riwayat panggilan terkini, dan pemanggilan audio Text-to-Speech (Fase 3).
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full border-t border-white/10 py-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir &bull; Zona Waktu: {{ config('app.timezone') }}
        </footer>
    </body>
</html>
