<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Meja Resepsionis — Pemanggilan Antrean') }}
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500" id="last-update">
                    Update: {{ now()->format('H:i:s') }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    {{ now()->translatedFormat('l, d M Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="queueManager()" x-init="startPolling()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Statistik Ringkas --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white rounded-xl border border-slate-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-slate-800" x-text="stats.total">{{ $stats['total'] }}</div>
                    <div class="text-xs text-slate-500 font-medium mt-1">Total Hari Ini</div>
                </div>
                <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-amber-700" x-text="stats.menunggu">{{ $stats['menunggu'] }}</div>
                    <div class="text-xs text-amber-600 font-medium mt-1">Menunggu</div>
                </div>
                <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-blue-700" x-text="stats.dipanggil">{{ $stats['dipanggil'] }}</div>
                    <div class="text-xs text-blue-600 font-medium mt-1">Dipanggil</div>
                </div>
                <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-indigo-700" x-text="stats.dilayani">{{ $stats['dilayani'] }}</div>
                    <div class="text-xs text-indigo-600 font-medium mt-1">Dilayani</div>
                </div>
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-emerald-700" x-text="stats.selesai">{{ $stats['selesai'] }}</div>
                    <div class="text-xs text-emerald-600 font-medium mt-1">Selesai</div>
                </div>
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-center shadow-sm">
                    <div class="text-2xl font-extrabold text-slate-500" x-text="stats.tidak_hadir">{{ $stats['tidak_hadir'] }}</div>
                    <div class="text-xs text-slate-500 font-medium mt-1">Tidak Hadir</div>
                </div>
            </div>

            {{-- Pilihan Loket --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center gap-4 flex-wrap">
                    <label class="text-sm font-semibold text-slate-700">Panggil ke:</label>
                    <div class="flex gap-2">
                        <template x-for="loket in ['Loket 1', 'Loket 2', 'Loket 3']" :key="loket">
                            <button type="button"
                                    @click="counterLabel = loket"
                                    :class="counterLabel === loket
                                        ? 'bg-blue-900 text-white shadow-lg shadow-blue-900/20'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                                    x-text="loket">
                            </button>
                        </template>
                    </div>
                    <a href="{{ route('layar') }}" target="_blank" class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-blue-700 hover:text-blue-900 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Buka Layar TV
                    </a>
                </div>
            </div>

            {{-- Tabel Antrean --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-left">
                                <th class="px-4 py-3 font-semibold text-slate-600 w-24">No. Antrean</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Nama Tamu</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Instansi</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Layanan</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Petugas</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 w-28">Status</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 text-center w-56">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visits as $visit)
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition
                                    {{ $visit->status === 'dipanggil' ? 'bg-blue-50/50' : '' }}
                                    {{ $visit->status === 'tidak_hadir' ? 'opacity-50' : '' }}
                                    {{ $visit->status === 'selesai' ? 'opacity-40' : '' }}"
                                    id="row-{{ $visit->id }}">
                                    <td class="px-4 py-3">
                                        <span class="font-mono font-extrabold text-lg
                                            {{ $visit->status === 'dipanggil' ? 'text-blue-700' : 'text-slate-900' }}">
                                            {{ $visit->queue_number }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $visit->full_name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $visit->institution }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                            {{ $visit->purpose === 'pst' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                            {{ $visit->purpose === 'non_pst' ? 'bg-teal-100 text-teal-800' : '' }}
                                            {{ $visit->purpose === 'pengaduan' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ strtoupper(str_replace('_', '-', $visit->purpose)) }}
                                        </span>
                                        @if($visit->service_type)
                                            <span class="block text-xs text-slate-500 mt-0.5">{{ $visit->service_type }}</span>
                                        @elseif($visit->non_pst_type)
                                            <span class="block text-xs text-slate-500 mt-0.5">{{ $visit->non_pst_type }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(in_array($visit->status, ['menunggu', 'dipanggil']))
                                            <form method="POST" action="{{ route('resepsionis.reassign', $visit) }}" class="inline-flex items-center">
                                                @csrf
                                                <select name="officer_id" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1 pl-2 pr-6 bg-slate-50 focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">-- Pilih Petugas --</option>
                                                    @foreach($officers as $off)
                                                        <option value="{{ $off->id }}" {{ $visit->assigned_officer_id === $off->id ? 'selected' : '' }}>
                                                            {{ $off->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-700 font-medium">
                                                {{ $visit->assignedOfficer?->name ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusBadge = match($visit->status) {
                                                'menunggu' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'dipanggil' => 'bg-blue-100 text-blue-800 border-blue-200 animate-pulse',
                                                'dilayani' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                'selesai' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'tidak_hadir' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                'dibatalkan' => 'bg-red-100 text-red-800 border-red-200',
                                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                                            };
                                            $statusLabel = match($visit->status) {
                                                'menunggu' => 'Menunggu',
                                                'dipanggil' => 'Dipanggil',
                                                'dilayani' => 'Dilayani',
                                                'selesai' => 'Selesai',
                                                'tidak_hadir' => 'Tidak Hadir',
                                                'dibatalkan' => 'Dibatalkan',
                                                default => $visit->status,
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($visit->status === 'menunggu')
                                                <form method="POST" action="{{ route('resepsionis.call', $visit) }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="counter_label" :value="counterLabel">
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                                        Panggil
                                                    </button>
                                                </form>
                                            @elseif($visit->status === 'dipanggil')
                                                <form method="POST" action="{{ route('resepsionis.recall', $visit) }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="counter_label" :value="counterLabel">
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                        Ulang
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('resepsionis.skip', $visit) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition"
                                                            onclick="return confirm('Lewati nomor {{ $visit->queue_number }}?')">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                                        Lewati
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                            <p class="text-slate-500 font-medium">Belum ada antrean hari ini</p>
                                            <p class="text-xs text-slate-400">Tamu kios yang mendaftar akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function queueManager() {
            return {
                counterLabel: 'Loket 1',
                stats: @json($stats),
                pollInterval: null,

                startPolling() {
                    // Polling tiap 5 detik untuk update data
                    this.pollInterval = setInterval(() => {
                        this.fetchData();
                    }, 5000);
                },

                async fetchData() {
                    try {
                        const res = await fetch('{{ route("resepsionis.api.queue") }}');
                        const data = await res.json();
                        this.stats = data.stats;
                        document.getElementById('last-update').textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
                    } catch (e) {
                        console.error('Polling error:', e);
                    }
                },

                destroy() {
                    if (this.pollInterval) clearInterval(this.pollInterval);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
