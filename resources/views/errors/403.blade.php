<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak | Buku Tamu BPS Inhil</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-slate-200 text-center">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-slate-800">Akses Ditolak (403)</h1>
        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
        </p>

        <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-left text-slate-500 space-y-1">
            <p><strong>Bagi Petugas/Admin:</strong> Daftarkan perangkat ini melalui perintah:</p>
            <code class="block font-mono bg-white p-2 rounded border border-slate-200 text-blue-900 font-semibold">
                php artisan kiosk:register "Nama Perangkat"
            </code>
            <p>Lalu buka URL aktivasi yang dihasilkan pada peramban ini.</p>
        </div>

        <div class="mt-6 flex flex-col gap-2">
            <a href="{{ url('/layanan') }}" class="w-full inline-flex justify-center items-center py-2.5 px-4 rounded-xl bg-blue-900 hover:bg-blue-800 text-white text-sm font-semibold transition">
                Buka Layanan Online
            </a>
            <a href="{{ url('/') }}" class="w-full inline-flex justify-center items-center py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium transition">
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
