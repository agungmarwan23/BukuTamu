<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Tiket Antrean &bull; {{ $visit->queue_number ?? $visit->ticket_code }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Styling Struk Cetak 58 mm (area efektif ~48 mm) */
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
                width: 48mm;
                margin: 0 auto;
                font-family: monospace;
                font-size: 11px;
                line-height: 1.25;
            }
        }
        @media screen {
            .print-only {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-slate-800 min-h-screen flex flex-col justify-between select-none">
    <!-- Header Kios -->
    <header class="no-print bg-slate-900/80 backdrop-blur-md border-b border-white/10 px-6 py-4 flex items-center justify-between text-white">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-amber-400 text-lg shadow-lg">
                BPS
            </div>
            <div>
                <h1 class="text-sm font-bold tracking-tight text-white leading-tight">PELAYANAN STATISTIK TERPADU</h1>
                <p class="text-[11px] font-semibold text-amber-400">BPS KABUPATEN INDRAGIRI HILIR</p>
            </div>
        </div>

        <div class="text-xs text-slate-300">
            Auto-reset: <span id="countdown-badge" class="font-mono font-bold text-amber-400 text-sm">30</span> detik
        </div>
    </header>

    <!-- Main Content Screen -->
    <main class="no-print w-full max-w-xl mx-auto px-4 py-8 flex-grow flex flex-col justify-center text-center">
        <div class="bg-white rounded-3xl shadow-2xl p-8 border border-slate-100 flex flex-col items-center">
            <!-- Icon Check -->
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900">Registrasi Berhasil!</h2>

            @if($visit->queue_number)
                {{-- Nomor Antrean Besar (elemen utama) --}}
                <p class="text-xs text-slate-500 mt-1">Nomor antrean Anda:</p>
                <div class="my-6 px-10 py-6 rounded-2xl bg-gradient-to-br from-blue-900 to-blue-800 text-white shadow-xl shadow-blue-900/30">
                    <div class="text-xs uppercase tracking-widest text-blue-200 font-semibold mb-1">NOMOR ANTREAN</div>
                    <div class="text-5xl sm:text-6xl font-black tracking-tight font-mono">{{ $visit->queue_number }}</div>
                </div>

                {{-- Kode Tiket Kecil (referensi) --}}
                <div class="px-4 py-2 rounded-lg bg-slate-50 border border-slate-200 inline-flex items-center gap-2 mb-4">
                    <span class="text-[11px] text-slate-500">Kode Tiket:</span>
                    <span class="text-sm font-bold font-mono text-slate-700">{{ $visit->ticket_code }}</span>
                </div>
            @else
                {{-- Fallback jika nomor antrean tidak ada (seharusnya tidak terjadi untuk kios) --}}
                <p class="text-xs text-slate-500 mt-1">Nomor tiket pendaftaran kunjungan Anda:</p>
                <div class="my-6 px-8 py-5 rounded-2xl bg-slate-50 border-2 border-blue-900 text-blue-900 shadow-inner">
                    <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold mb-1">KODE TIKET KUNJUNGAN</div>
                    <div class="text-3xl sm:text-4xl font-black tracking-tight font-mono">{{ $visit->ticket_code }}</div>
                </div>
            @endif

            <!-- Detail Singkat -->
            <div class="w-full text-left text-xs bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-2 mb-6">
                <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500">Nama Lengkap:</span>
                    <span class="font-bold text-slate-800">{{ $visit->full_name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500">Instansi:</span>
                    <span class="font-semibold text-slate-800">{{ $visit->institution }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500">Layanan:</span>
                    <span class="font-semibold text-slate-800 uppercase">{{ $visit->purpose }} ({{ $visit->service_type ?: $visit->non_pst_type ?: 'Pengaduan' }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Waktu Kedatangan:</span>
                    <span class="font-mono text-slate-700">{{ $visit->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="w-full flex flex-col sm:flex-row gap-3">
                <button type="button" onclick="printReceipt()"
                        class="flex-1 py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-bold text-sm transition flex items-center justify-center gap-2 shadow-lg shadow-blue-900/20">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Tiket Antrean</span>
                </button>

                <a href="{{ route('kios.index') }}"
                   class="py-3.5 px-6 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition">
                    Selesai & Ke Awal
                </a>
            </div>

            <p class="text-[11px] text-slate-400 mt-4">
                Halaman akan kembali otomatis ke form awal dalam <span id="countdown-text" class="font-bold text-slate-700">30</span> detik.
            </p>
        </div>
    </main>

    <!-- Struk Print Only (58mm Thermal Printer Layout) -->
    <div class="print-only">
        <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 4px;">
            <div style="font-weight: bold; font-size: 13px;">BPS KAB. INHIL</div>
            <div>Pelayanan Statistik Terpadu</div>
            <div style="font-size: 9px;">Jl. Veteran No. 1, Tembilahan</div>
        </div>

        @if($visit->queue_number)
            <div style="text-align: center; margin: 8px 0;">
                <div style="font-size: 10px;">NOMOR ANTREAN:</div>
                <div style="font-size: 28px; font-weight: bold; letter-spacing: 2px;">{{ $visit->queue_number }}</div>
            </div>
            <div style="text-align: center; font-size: 9px; border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 4px;">
                Kode: {{ $visit->ticket_code }}
            </div>
        @else
            <div style="text-align: center; margin: 8px 0;">
                <div style="font-size: 10px;">KODE TIKET:</div>
                <div style="font-size: 16px; font-weight: bold;">{{ $visit->ticket_code }}</div>
            </div>
        @endif

        <div style="border-top: 1px dashed #000; padding-top: 4px; margin-top: 4px; font-size: 10px;">
            <div>Nama: {{ Str::limit($visit->full_name, 22) }}</div>
            <div>Instansi: {{ Str::limit($visit->institution, 22) }}</div>
            <div>Layanan: {{ strtoupper($visit->purpose) }}</div>
            <div>Waktu: {{ $visit->created_at->format('d/m/Y H:i') }} WIB</div>
        </div>

        <div style="text-align: center; border-top: 1px dashed #000; padding-top: 4px; margin-top: 6px; font-size: 9px;">
            Silakan menunggu nomor Anda dipanggil oleh petugas loket.<br>
            Terima kasih atas kunjungan Anda.
        </div>
    </div>

    <!-- Footer -->
    <footer class="no-print bg-slate-900/90 text-center py-3 text-xs text-slate-400 border-t border-white/5">
        &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir
    </footer>

    {{-- Dokumentasi: Chrome --kiosk-printing
         Untuk melewati dialog cetak di PC kios, jalankan Chrome:
         chrome.exe --kiosk --kiosk-printing http://localhost:8000/kios
         Ini akan langsung mencetak tanpa dialog konfirmasi.
    --}}

    <script>
        function printReceipt() {
            window.print();
        }

        // Auto-reset countdown 30 detik
        let countdown = 30;
        const countdownBadge = document.getElementById('countdown-badge');
        const countdownText = document.getElementById('countdown-text');

        const interval = setInterval(function() {
            countdown--;
            if (countdownBadge) countdownBadge.textContent = countdown;
            if (countdownText) countdownText.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = "{{ route('kios.index') }}";
            }
        }, 1000);
    </script>
</body>
</html>
