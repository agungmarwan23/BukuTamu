<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layar Antrean — BPS Kab. Indragiri Hilir</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Animasi kedip untuk nomor yang sedang dipanggil */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3), 0 0 60px rgba(59, 130, 246, 0.1); }
            50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.6), 0 0 100px rgba(59, 130, 246, 0.3); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        @keyframes slide-in {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .slide-in {
            animation: slide-in 0.5s ease-out;
        }

        @keyframes bounce-in {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.95); }
            100% { transform: scale(1); opacity: 1; }
        }
        .bounce-in {
            animation: bounce-in 0.6s ease-out;
        }

        /* Running text */
        @keyframes scroll-left {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .scroll-text {
            animation: scroll-left 30s linear infinite;
        }

        /* Fullscreen styling */
        body { cursor: none; overflow: hidden; }
        body.has-cursor { cursor: default; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white min-h-screen flex flex-col" x-data="queueScreen()" x-init="init()">

    {{-- Header --}}
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-white/10 px-6 py-4 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center font-extrabold text-amber-400 text-xl shadow-lg shadow-blue-600/30">
                BPS
            </div>
            <div>
                <h1 class="text-lg font-extrabold tracking-tight text-white leading-tight">PELAYANAN STATISTIK TERPADU</h1>
                <p class="text-xs font-bold text-amber-400 tracking-wider">BPS KABUPATEN INDRAGIRI HILIR</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-extrabold text-white font-mono" x-text="clock">--:--:--</div>
            <div class="text-xs text-slate-400" x-text="dateStr">--</div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-grow flex gap-6 p-6 overflow-hidden">

        {{-- Panel Kiri: Nomor Sedang Dipanggil (besar) --}}
        <div class="flex-1 flex flex-col items-center justify-center">
            {{-- Tombol Aktifkan Suara (wajib karena autoplay policy) --}}
            <div x-show="!soundEnabled" x-transition
                 class="mb-8 has-cursor">
                <button @click="enableSound()"
                        class="px-8 py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-900 font-extrabold text-lg transition-all shadow-xl shadow-amber-500/30 flex items-center gap-3 cursor-pointer">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                    Aktifkan Suara
                </button>
            </div>

            <template x-if="current">
                <div class="flex flex-col items-center bounce-in" :key="current.call_event_id">
                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">NOMOR ANTREAN</p>

                    <div class="pulse-glow rounded-3xl bg-gradient-to-br from-blue-600 to-blue-800 px-16 py-10 flex flex-col items-center border-2 border-blue-400/30">
                        <span class="text-8xl sm:text-9xl font-black tracking-tight font-mono text-white drop-shadow-lg" x-text="current.queue_number">
                        </span>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <div class="px-5 py-2.5 rounded-xl bg-amber-500/20 border border-amber-400/30 text-amber-400">
                            <span class="text-sm font-bold">Silakan menuju</span>
                            <span class="text-xl font-extrabold ml-1" x-text="current.counter_label"></span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-4">
                        Dipanggil pukul <span class="font-mono font-bold text-slate-300" x-text="current.called_at"></span>
                    </p>
                </div>
            </template>

            <template x-if="!current">
                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-xl font-bold text-slate-500">Belum ada panggilan</p>
                    <p class="text-sm text-slate-600 mt-1">Menunggu panggilan dari resepsionis...</p>
                </div>
            </template>
        </div>

        {{-- Panel Kanan: Riwayat & Info --}}
        <div class="w-80 lg:w-96 flex flex-col gap-4 shrink-0">

            {{-- Jumlah Menunggu --}}
            <div class="bg-slate-800/60 backdrop-blur rounded-2xl border border-white/10 p-5 text-center">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tamu Menunggu</p>
                <p class="text-5xl font-black text-amber-400 mt-1 font-mono" x-text="waitingCount">0</p>
                <p class="text-xs text-slate-500 mt-1">orang dalam antrean</p>
            </div>

            {{-- Riwayat 5 Panggilan Terakhir --}}
            <div class="bg-slate-800/60 backdrop-blur rounded-2xl border border-white/10 p-5 flex-grow">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-4">Riwayat Panggilan</p>

                <div class="space-y-2">
                    <template x-for="call in recent" :key="call.call_event_id">
                        <div class="flex items-center justify-between bg-slate-700/40 rounded-xl px-4 py-3 border border-white/5 slide-in">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-extrabold font-mono"
                                      :class="call.status === 'selesai' ? 'text-emerald-400' :
                                              call.status === 'tidak_hadir' ? 'text-slate-500' :
                                              call.status === 'dilayani' ? 'text-indigo-400' : 'text-white'"
                                      x-text="call.queue_number"></span>
                                <span class="text-xs text-slate-500" x-text="call.counter_label"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-mono text-slate-500" x-text="call.called_at"></span>
                                <span class="block text-[10px] font-bold uppercase tracking-wider"
                                      :class="call.status === 'selesai' ? 'text-emerald-500' :
                                              call.status === 'tidak_hadir' ? 'text-slate-600' :
                                              call.status === 'dilayani' ? 'text-indigo-400' : 'text-blue-400'"
                                      x-text="statusLabel(call.status)"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="recent.length === 0">
                        <p class="text-center text-sm text-slate-600 py-6">Belum ada riwayat panggilan</p>
                    </template>
                </div>
            </div>
        </div>
    </main>

    {{-- Running Text Footer --}}
    <footer class="bg-slate-900/90 border-t border-white/10 px-4 py-2 overflow-hidden shrink-0">
        <div class="whitespace-nowrap scroll-text text-sm text-amber-400/80 font-medium">
            Selamat datang di Badan Pusat Statistik Kabupaten Indragiri Hilir — Pelayanan Statistik Terpadu — Mohon menunggu nomor antrean Anda dipanggil — Terima kasih atas kunjungan Anda
        </div>
    </footer>

    <script>
        function queueScreen() {
            return {
                current: null,
                recent: [],
                waitingCount: 0,
                lastCallEventId: null,
                soundEnabled: false,
                clock: '--:--:--',
                dateStr: '--',
                pollInterval: null,
                clockInterval: null,

                init() {
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);
                    this.fetchData();
                    // Polling tiap 3 detik (FR-13)
                    this.pollInterval = setInterval(() => this.fetchData(), 3000);
                },

                updateClock() {
                    const now = new Date();
                    this.clock = now.toLocaleTimeString('id-ID', { hour12: false });
                    this.dateStr = now.toLocaleDateString('id-ID', {
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                    });
                },

                enableSound() {
                    this.soundEnabled = true;
                    // Test utterance (browser membutuhkan user gesture)
                    const utterance = new SpeechSynthesisUtterance('Suara aktif');
                    utterance.lang = 'id-ID';
                    utterance.volume = 0.5;
                    speechSynthesis.speak(utterance);
                    document.body.classList.add('has-cursor');
                    setTimeout(() => document.body.classList.remove('has-cursor'), 3000);
                },

                async fetchData() {
                    try {
                        const res = await fetch('{{ route("api.layar") }}');
                        const data = await res.json();

                        this.waitingCount = data.waiting_count;
                        this.recent = data.recent || [];

                        // Cek apakah ada panggilan baru
                        if (data.current) {
                            const isNewCall = data.current.call_event_id !== this.lastCallEventId;
                            this.current = data.current;

                            if (isNewCall && this.soundEnabled) {
                                this.speak(data.current.queue_number, data.current.counter_label);
                            }
                            this.lastCallEventId = data.current.call_event_id;
                        } else {
                            this.current = null;
                        }
                    } catch (e) {
                        console.error('Polling error:', e);
                    }
                },

                /**
                 * TTS: Mengucapkan nomor antrean dengan jelas.
                 * Contoh: PST-01 → "Nomor antrean P S T nol satu, silakan menuju Loket satu"
                 */
                speak(queueNumber, counterLabel) {
                    if (!('speechSynthesis' in window)) return;

                    // Bersihkan antrian suara sebelumnya
                    speechSynthesis.cancel();

                    const spoken = this.numberToSpeech(queueNumber);
                    const counterSpoken = this.counterToSpeech(counterLabel);

                    // Ucapkan 2 kali dengan jeda
                    const text1 = `Nomor antrean ${spoken}, silakan menuju ${counterSpoken}`;
                    const text2 = `Sekali lagi, nomor antrean ${spoken}, silakan menuju ${counterSpoken}`;

                    const u1 = new SpeechSynthesisUtterance(text1);
                    u1.lang = 'id-ID';
                    u1.rate = 0.85;
                    u1.pitch = 1.0;
                    u1.volume = 1.0;

                    const u2 = new SpeechSynthesisUtterance(text2);
                    u2.lang = 'id-ID';
                    u2.rate = 0.85;
                    u2.pitch = 1.0;
                    u2.volume = 1.0;

                    speechSynthesis.speak(u1);
                    speechSynthesis.speak(u2);
                },

                /**
                 * Konversi nomor antrean ke bentuk ucapan.
                 * PST-01 → "P S T nol satu"
                 * PST-12 → "P S T satu dua"
                 * PST-100 → "P S T satu nol nol"
                 */
                numberToSpeech(queueNumber) {
                    const digitWords = {
                        '0': 'nol', '1': 'satu', '2': 'dua', '3': 'tiga', '4': 'empat',
                        '5': 'lima', '6': 'enam', '7': 'tujuh', '8': 'delapan', '9': 'sembilan'
                    };

                    // Pisah prefix dan angka: PST-01 → ["PST", "01"]
                    const parts = queueNumber.split('-');
                    const prefix = parts[0].split('').join(' '); // P S T
                    const digits = parts[1].split('').map(d => digitWords[d] || d).join(' ');

                    return `${prefix} ${digits}`;
                },

                counterToSpeech(label) {
                    const digitWords = {
                        '1': 'satu', '2': 'dua', '3': 'tiga', '4': 'empat', '5': 'lima',
                        '6': 'enam', '7': 'tujuh', '8': 'delapan', '9': 'sembilan'
                    };
                    // "Loket 1" → "Loket satu"
                    return label.replace(/(\d)/g, (m) => digitWords[m] || m);
                },

                statusLabel(status) {
                    const map = {
                        'menunggu': 'Menunggu',
                        'dipanggil': 'Dipanggil',
                        'dilayani': 'Dilayani',
                        'selesai': 'Selesai',
                        'tidak_hadir': 'Tidak Hadir',
                        'dibatalkan': 'Dibatalkan',
                    };
                    return map[status] || status;
                }
            };
        }
    </script>
</body>
</html>
