<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Dasbor Eksekutif & Analitik Layanan') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Ringkasan performa operasional buku tamu, antrean PST, dan tren pelayanan BPS Kab. Indragiri Hilir.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-900 border border-blue-200 shadow-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if (session('success'))
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- 1. KPI Metrik Utama (4 Kartu) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kunjungan Hari Ini</span>
                        <span class="p-2 bg-blue-50 text-blue-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ $todayCount }}</span>
                        <span class="text-xs text-slate-500">tamu</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 flex items-center justify-between border-t border-slate-100 pt-2">
                        <span>7 Hari Terakhir: <strong>{{ $weekCount }}</strong></span>
                        <span>Bulan Ini: <strong>{{ $monthCount }}</strong></span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Tiket Belum Selesai</span>
                        <span class="p-2 bg-amber-100 text-amber-800 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-600">{{ $activeCount }}</span>
                        <span class="text-xs text-amber-700">antrean aktif</span>
                    </div>
                    <div class="mt-2 text-xs text-amber-800 border-t border-amber-100 pt-2">
                        Status Menunggu / Dipanggil / Dilayani
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rata-rata Waktu Tunggu</span>
                        <span class="p-2 bg-indigo-50 text-indigo-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-indigo-700">{{ $avgWaitTime }}</span>
                        <span class="text-xs text-slate-500">menit</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2">
                        Registrasi &rarr; Dilayani (Bulan Berjalan)
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rata-rata Waktu Layanan</span>
                        <span class="p-2 bg-emerald-50 text-emerald-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-emerald-700">{{ $avgServiceTime }}</span>
                        <span class="text-xs text-slate-500">menit</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2">
                        Mulai Layani &rarr; Selesai
                    </div>
                </div>
            </div>

            <!-- 2. Grafik Tren & Distribusi (Chart.js Lokal) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Grafik Tren 7 Hari -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Tren Kunjungan 7 Hari Terakhir</h3>
                            <p class="text-xs text-slate-500">Perbandingan tamu lobi (Kios) dan konsultasi daring (Online).</p>
                        </div>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Grafik Distribusi Layanan -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Distribusi Jenis Layanan</h3>
                        <p class="text-xs text-slate-500 mb-4">Proporsi keperluan tamu pada bulan ini.</p>
                        <div class="relative h-56 w-full flex items-center justify-center">
                            @if (empty($serviceLabels))
                                <div class="text-xs text-slate-400">Belum ada data layanan bulan ini.</div>
                            @else
                                <canvas id="serviceChart"></canvas>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Informasi Pendukung & Operasional -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Top 5 Instansi Pengunjung -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="font-bold text-slate-800 text-base mb-1">Top Asal Instansi Tamu</h3>
                    <p class="text-xs text-slate-500 mb-4">Instansi/lembaga yang paling sering berkunjung bulan ini.</p>

                    @if ($topInstitutions->isEmpty())
                        <div class="text-center py-8 text-xs text-slate-400">Belum ada kunjungan bulan ini.</div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($topInstitutions as $inst)
                                <li class="py-2.5 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-700 truncate max-w-[200px]">{{ $inst->institution ?: 'Masyarakat Umum' }}</span>
                                    <span class="font-mono font-bold bg-slate-100 text-slate-800 px-2 py-0.5 rounded-full">{{ $inst->total }} tamu</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Petugas Piket Hari Ini -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Petugas Piket Hari Ini</h3>
                            <p class="text-xs text-slate-500">Memperhitungkan jadwal swap aktif.</p>
                        </div>
                        <a href="{{ route('admin.schedules.index') }}" class="text-xs font-bold text-blue-700 hover:underline">Kelola &rarr;</a>
                    </div>

                    @if ($onDutyOfficers->isEmpty())
                        <div class="p-6 text-center text-xs text-amber-700 bg-amber-50 rounded-xl border border-amber-200">
                            ⚠️ Belum ada petugas piket yang ditetapkan untuk hari ini.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($onDutyOfficers as $off)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="w-8 h-8 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($off->name, 0, 1)) }}
                                    </div>
                                    <div class="truncate flex-1">
                                        <div class="font-bold text-slate-800 text-xs truncate">{{ $off->name }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono">{{ $off->username }} &bull; {{ $off->role }}</div>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Swap Jadwal Terbaru -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Swap Jadwal Terbaru</h3>
                            <p class="text-xs text-slate-500">Riwayat pertukaran piket terkini.</p>
                        </div>
                        <a href="{{ route('admin.analytics.swaps') }}" class="text-xs font-bold text-blue-700 hover:underline">Analitik &rarr;</a>
                    </div>

                    @if ($recentSwaps->isEmpty())
                        <div class="text-center py-8 text-xs text-slate-400">Belum ada aktivitas pertukaran jadwal.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentSwaps as $sw)
                                <div class="text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                                        <span>{{ $sw->duty_date->format('d M Y') }}</span>
                                        <span>{{ $sw->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="font-medium text-slate-700">
                                        <span class="text-amber-700">{{ $sw->originalOfficer?->name }}</span>
                                        &rarr;
                                        <span class="text-blue-700 font-bold">{{ $sw->replacementOfficer?->name }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 italic truncate">
                                        "{{ $sw->reason }}"
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Chart Tren 7 Hari (Chart.js Lokal)
            const trendCtx = document.getElementById('trendChart')?.getContext('2d');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($trendDates) !!},
                        datasets: [
                            {
                                label: 'Kios Lobi',
                                data: {!! json_encode($trendKiosk) !!},
                                backgroundColor: '#1e3a8a',
                                borderRadius: 6,
                            },
                            {
                                label: 'Konsultasi Online',
                                data: {!! json_encode($trendOnline) !!},
                                backgroundColor: '#38bdf8',
                                borderRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { font: { size: 11, family: 'sans-serif' } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, font: { size: 10 } },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 } }
                            }
                        }
                    }
                });
            }

            // 2. Chart Distribusi Layanan (Doughnut)
            const serviceCtx = document.getElementById('serviceChart')?.getContext('2d');
            if (serviceCtx) {
                new Chart(serviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode($serviceLabels) !!},
                        datasets: [{
                            data: {!! json_encode($serviceValues) !!},
                            backgroundColor: [
                                '#1e3a8a', '#2563eb', '#38bdf8', '#10b981', '#f59e0b', '#ef4444'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { size: 10 }, boxWidth: 12 }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
