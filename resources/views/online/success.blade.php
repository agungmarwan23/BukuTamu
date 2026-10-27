<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permohonan Terkirim &bull; {{ $visit->ticket_code }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 text-slate-800 min-h-screen flex flex-col justify-between">
    <!-- Navbar Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-900 flex items-center justify-center font-bold text-white text-base shadow-sm">
                    BPS
                </div>
                <div>
                    <div class="font-extrabold text-blue-950 text-sm tracking-tight leading-tight">LAYANAN STATISTIK ONLINE</div>
                    <div class="text-[11px] font-semibold text-amber-600">BPS KABUPATEN INDRAGIRI HILIR</div>
                </div>
            </a>
        </div>
    </header>

    <!-- Main Content Screen -->
    <main class="w-full max-w-xl mx-auto px-4 py-10 flex-grow flex flex-col justify-center text-center">
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 p-8 border border-slate-200/80 flex flex-col items-center">
            <!-- Icon Check -->
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>

            <h1 class="text-2xl font-extrabold text-slate-900">Permohonan Berhasil Dikirim!</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md">
                Terima kasih, <strong>{{ $visit->full_name }}</strong>. Permohonan Anda telah masuk ke sistem Pelayanan Statistik Terpadu BPS Kab. Indragiri Hilir.
            </p>

            <!-- Kode Tiket Online -->
            <div class="my-6 px-8 py-5 rounded-2xl bg-blue-50/80 border-2 border-blue-900 text-blue-900 shadow-inner w-full max-w-sm">
                <div class="text-[11px] uppercase tracking-widest text-slate-500 font-semibold mb-1">KODE TIKET LAYANAN ONLINE</div>
                <div class="text-2xl sm:text-3xl font-black tracking-tight font-mono">{{ $visit->ticket_code }}</div>
            </div>

            <!-- Detail Permohonan -->
            <div class="w-full text-left text-xs bg-slate-50 rounded-2xl p-5 border border-slate-200/70 space-y-2 mb-6">
                <div class="flex justify-between border-b border-slate-200/60 pb-2">
                    <span class="text-slate-500">Nama Pemohon:</span>
                    <span class="font-bold text-slate-800">{{ $visit->full_name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-2">
                    <span class="text-slate-500">Instansi / Lembaga:</span>
                    <span class="font-semibold text-slate-800">{{ $visit->institution }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-2">
                    <span class="text-slate-500">Tujuan Layanan:</span>
                    <span class="font-semibold text-slate-800 uppercase">{{ $visit->purpose }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-2">
                    <span class="text-slate-500">Kontak WhatsApp:</span>
                    <span class="font-mono text-slate-800">+{{ $visit->phone }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Waktu Kirim:</span>
                    <span class="font-mono text-slate-700">{{ $visit->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
            </div>

            <!-- Pesan Tindak Lanjut -->
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200/80 text-left text-xs text-amber-900 space-y-1 mb-6">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Langkah Selanjutnya:</span>
                </div>
                <p class="leading-relaxed">
                    Petugas piket PST kami akan menelaah data permohonan Anda dan menghubungi Anda melalui <strong>WhatsApp</strong> atau <strong>Email</strong> pada jam operasional kerja (Senin - Jumat, 08:00 - 15:30 WIB).
                </p>
            </div>

            <!-- Tombol Aksi -->
            <div class="w-full flex flex-col sm:flex-row gap-3">
                <a href="{{ url('/layanan') }}"
                   class="flex-1 py-3 px-5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm transition shadow-md">
                    Kirim Permohonan Lain
                </a>

                <a href="{{ url('/') }}"
                   class="py-3 px-5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition">
                    Halaman Utama
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 text-center py-4 text-xs text-slate-500">
        &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir
    </footer>
</body>
</html>
