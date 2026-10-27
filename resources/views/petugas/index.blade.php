<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-700 rounded-full inline-block"></span>
                    {{ __('Portal Petugas Layanan PST') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Pelayanan antrean tamu lobi, tindak lanjut konsultasi online, dan pengaturan jadwal piket.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200 shadow-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                    {{ Auth::user()->role }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{
        activeTab: '{{ $tab }}',
        activePhotoModal: null,
        activeFinishModal: null,
        activeOnlineModal: null,
        activeCancelModal: null,
        selectedVisit: null
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if (session('success'))
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm transition-all">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm transition-all">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    <div class="font-semibold mb-1">Harap perbaiki kesalahan berikut:</div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Navigation Tabs -->
            <div class="bg-white rounded-2xl p-1.5 border border-slate-200 shadow-sm flex flex-wrap gap-1.5 sm:gap-2">
                <button type="button"
                    @click="activeTab = 'antrean'"
                    :class="activeTab === 'antrean' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Antrean Saya (Hari Ini)</span>
                    <span :class="activeTab === 'antrean' ? 'bg-blue-800 text-blue-100' : 'bg-slate-200 text-slate-700'" class="px-2 py-0.5 rounded-full text-xs font-bold">
                        {{ $stats['total'] }}
                    </span>
                </button>

                <button type="button"
                    @click="activeTab = 'online'"
                    :class="activeTab === 'online' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    <span>Permintaan Online</span>
                    <span :class="activeTab === 'online' ? 'bg-blue-800 text-blue-100' : 'bg-slate-200 text-slate-700'" class="px-2 py-0.5 rounded-full text-xs font-bold">
                        {{ $onlineVisits->total() }}
                    </span>
                </button>

                <button type="button"
                    @click="activeTab = 'jadwal'"
                    :class="activeTab === 'jadwal' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Jadwal Saya</span>
                </button>

                <button type="button"
                    @click="activeTab = 'swap'"
                    :class="activeTab === 'swap' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Tukar Jadwal (Swap)</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: ANTREAN SAYA HARI INI               -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'antrean'" x-transition class="space-y-6">
                <!-- Mini Stats Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Ditugaskan</div>
                        <div class="text-2xl font-black text-slate-800 mt-1">{{ $stats['total'] }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/30 shadow-sm">
                        <div class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Menunggu</div>
                        <div class="text-2xl font-black text-amber-600 mt-1">{{ $stats['menunggu'] }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-blue-200 bg-blue-50/30 shadow-sm">
                        <div class="text-xs font-semibold text-blue-700 uppercase tracking-wider">Dipanggil</div>
                        <div class="text-2xl font-black text-blue-600 mt-1">{{ $stats['dipanggil'] }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-indigo-200 bg-indigo-50/30 shadow-sm">
                        <div class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">Sedang Dilayani</div>
                        <div class="text-2xl font-black text-indigo-600 mt-1">{{ $stats['dilayani'] }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/30 shadow-sm">
                        <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Selesai</div>
                        <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['selesai'] }}</div>
                    </div>
                </div>

                <!-- Daftar Antrean Kios -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Daftar Antrean Tamu Kios Hari Ini</h3>
                            <p class="text-xs text-slate-500">
                                @if (Auth::user()->isAdmin())
                                    Mode Admin: Menampilkan seluruh antrean kios lobi hari ini.
                                @else
                                    Menampilkan antrean yang ditugaskan kepada Anda.
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($myVisits->isEmpty())
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-700 text-sm">Belum Ada Antrean</h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                Saat tamu mendaftar di Kios Lobi, nomor antrean akan otomatis terdistribusi ke petugas piket aktif.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-100/70 text-slate-600 uppercase text-xs font-semibold">
                                    <tr>
                                        <th class="px-4 py-3.5 text-center">No. Antrean</th>
                                        <th class="px-4 py-3.5">Foto</th>
                                        <th class="px-4 py-3.5">Tamu & Instansi</th>
                                        <th class="px-4 py-3.5">Keperluan & Layanan</th>
                                        <th class="px-4 py-3.5 text-center">Status</th>
                                        <th class="px-4 py-3.5 text-right">Aksi Layanan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($myVisits as $visit)
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $visit->status === 'dilayani' ? 'bg-indigo-50/30' : '' }}">
                                            <!-- Nomor Antrean -->
                                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                                <span class="inline-block px-3 py-1.5 rounded-xl font-mono font-black text-lg shadow-sm {{ $visit->status === 'dilayani' ? 'bg-indigo-600 text-white' : ($visit->status === 'dipanggil' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-white') }}">
                                                    {{ $visit->queue_number }}
                                                </span>
                                                <div class="text-[11px] font-mono text-slate-400 mt-1">
                                                    {{ $visit->ticket_code }}
                                                </div>
                                            </td>

                                            <!-- Foto Tamu Privat -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                @if ($visit->photo_path)
                                                    <div class="relative group cursor-pointer"
                                                        @click="activePhotoModal = '{{ route('visit.photo', $visit) }}'">
                                                        <img src="{{ route('visit.photo', $visit) }}"
                                                            alt="{{ $visit->full_name }}"
                                                            class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform" />
                                                        <div class="absolute inset-0 bg-slate-900/40 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-xs">
                                                        Tanpa Foto
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Data Tamu & Kontak -->
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                    {{ $visit->full_name }}
                                                </div>
                                                <div class="text-xs text-slate-600 mt-0.5">
                                                    {{ $visit->institution ?: 'Masyarakat Umum' }}
                                                </div>
                                                <div class="text-xs text-slate-500 mt-1 flex items-center gap-3">
                                                    <a href="tel:{{ $visit->phone }}" class="text-blue-600 hover:underline flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                        </svg>
                                                        {{ $visit->phone }}
                                                    </a>
                                                    @if ($visit->email)
                                                        <span class="text-slate-400">&bull;</span>
                                                        <span class="text-slate-500">{{ $visit->email }}</span>
                                                    @endif
                                                </div>
                                                @if (Auth::user()->isAdmin() && $visit->assignedOfficer)
                                                    <div class="mt-1 text-[11px] text-indigo-700">
                                                        Petugas: <strong>{{ $visit->assignedOfficer->name }}</strong>
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Keperluan & Lampiran -->
                                            <td class="px-4 py-4 max-w-xs">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $visit->purpose === 'pst' ? 'bg-blue-100 text-blue-800' : ($visit->purpose === 'pengaduan' ? 'bg-rose-100 text-rose-800' : 'bg-purple-100 text-purple-800') }}">
                                                        {{ $visit->purpose === 'pst' ? 'Layanan PST' : ($visit->purpose === 'pengaduan' ? 'Pengaduan' : 'Non-PST') }}
                                                    </span>
                                                    @if ($visit->service_type)
                                                        <span class="text-xs text-slate-600 font-medium truncate">
                                                            {{ $visit->service_type }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-slate-700 mt-1 line-clamp-2">
                                                    {{ $visit->detail }}
                                                </div>

                                                <!-- Lampiran berkas -->
                                                @if ($visit->attachments->isNotEmpty())
                                                    <div class="mt-2 flex flex-wrap gap-1">
                                                        @foreach ($visit->attachments as $att)
                                                            <a href="{{ route('visit.attachment', $att) }}"
                                                                target="_blank"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition-colors">
                                                                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                                </svg>
                                                                <span class="truncate max-w-[120px]">{{ $att->original_name }}</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Catatan Layanan (jika ada) -->
                                                @if ($visit->officer_note)
                                                    <div class="mt-2 p-2 bg-slate-50 border border-slate-200 rounded text-xs text-slate-700 italic">
                                                        "{{ $visit->officer_note }}"
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Status Badge -->
                                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                                @if ($visit->status === 'menunggu')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                        Menunggu
                                                    </span>
                                                @elseif ($visit->status === 'dipanggil')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 animate-pulse">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                        Dipanggil
                                                    </span>
                                                @elseif ($visit->status === 'dilayani')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span>
                                                        Sedang Dilayani
                                                    </span>
                                                @elseif ($visit->status === 'selesai')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Selesai
                                                    </span>
                                                @elseif ($visit->status === 'tidak_hadir')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                                        Tidak Hadir
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                                        {{ ucfirst($visit->status) }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Aksi Layanan -->
                                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-2">
                                                    <!-- Tombol Mulai Layani -->
                                                    @if (in_array($visit->status, ['menunggu', 'dipanggil']))
                                                        <form method="POST" action="{{ route('petugas.start', $visit) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                <span>Mulai Layani</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <!-- Tombol Selesai Layani (Modal) -->
                                                    @if ($visit->status === 'dilayani')
                                                        <button type="button"
                                                            @click="activeFinishModal = {{ $visit->id }}; selectedVisit = {{ json_encode(['id' => $visit->id, 'queue_number' => $visit->queue_number, 'full_name' => $visit->full_name]) }}"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span>Selesaikan</span>
                                                        </button>
                                                    @endif

                                                    <!-- Tombol Opsi Tambahan (Tidak Hadir / Batalkan) -->
                                                    @if (in_array($visit->status, ['menunggu', 'dipanggil', 'dilayani']))
                                                        <button type="button"
                                                            @click="activeCancelModal = {{ $visit->id }}; selectedVisit = {{ json_encode(['id' => $visit->id, 'queue_number' => $visit->queue_number, 'full_name' => $visit->full_name]) }}"
                                                            title="Opsi status lainnya"
                                                            class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: PERMINTAAN ONLINE                   -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'online'" x-transition class="space-y-6" style="display: none;">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Permintaan Layanan Tamu Online</h3>
                            <p class="text-xs text-slate-500">Tindak lanjuti permohonan konsultasi via email atau WhatsApp.</p>
                        </div>
                    </div>

                    @if ($onlineVisits->isEmpty())
                        <div class="p-12 text-center text-slate-500">
                            Belum ada permohonan tiket online yang masuk.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-100/70 text-slate-600 uppercase text-xs font-semibold">
                                    <tr>
                                        <th class="px-4 py-3.5">Kode Tiket</th>
                                        <th class="px-4 py-3.5">Tamu & Kontak</th>
                                        <th class="px-4 py-3.5">Keperluan & Lampiran</th>
                                        <th class="px-4 py-3.5 text-center">Status</th>
                                        <th class="px-4 py-3.5 text-right">Tindak Lanjut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($onlineVisits as $visit)
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="font-mono font-bold text-sm text-slate-800 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                                    {{ $visit->ticket_code }}
                                                </span>
                                                <div class="text-[11px] text-slate-400 mt-1">
                                                    {{ $visit->created_at->format('d M Y, H:i') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-slate-900">{{ $visit->full_name }}</div>
                                                <div class="text-xs text-slate-500">{{ $visit->institution ?: 'Masyarakat Umum' }}</div>
                                                <div class="mt-1 flex items-center gap-2">
                                                    @php
                                                        $waPhone = preg_replace('/[^0-9]/', '', $visit->phone);
                                                    @endphp
                                                    <a href="https://wa.me/{{ $waPhone }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold border border-emerald-200">
                                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                                        </svg>
                                                        Hubungi WA
                                                    </a>
                                                    @if ($visit->email)
                                                        <a href="mailto:{{ $visit->email }}"
                                                            class="text-xs text-blue-600 hover:underline">
                                                            {{ $visit->email }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 max-w-sm">
                                                <div class="text-xs font-semibold text-slate-800">
                                                    {{ $visit->service_type ?: ucfirst($visit->purpose) }}
                                                </div>
                                                <div class="text-xs text-slate-600 mt-1 line-clamp-2">
                                                    {{ $visit->detail }}
                                                </div>
                                                @if ($visit->attachments->isNotEmpty())
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        @foreach ($visit->attachments as $att)
                                                            <a href="{{ route('visit.attachment', $att) }}" target="_blank"
                                                                class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                                                                📎 {{ $att->original_name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if ($visit->officer_note)
                                                    <div class="mt-1 text-xs text-slate-500 italic">
                                                        Catatan: {{ $visit->officer_note }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $visit->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : ($visit->status === 'dilayani' ? 'bg-indigo-100 text-indigo-800' : ($visit->status === 'dibatalkan' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')) }}">
                                                    {{ ucfirst($visit->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                                <button type="button"
                                                    @click="activeOnlineModal = {{ $visit->id }}; selectedVisit = {{ json_encode(['id' => $visit->id, 'ticket_code' => $visit->ticket_code, 'full_name' => $visit->full_name, 'status' => $visit->status, 'officer_note' => $visit->officer_note]) }}"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold text-xs border border-blue-200 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Tindak Lanjut
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200">
                            {{ $onlineVisits->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: JADWAL SAYA                         -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'jadwal'" x-transition class="space-y-6" style="display: none;">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Jadwal Piket Saya</h3>
                            <p class="text-xs text-slate-500">Menampilkan jadwal piket resmi dan penyesuaian pertukaran (swap) yang berlaku.</p>
                        </div>
                        <button type="button" @click="activeTab = 'swap'" class="text-xs font-semibold text-blue-700 hover:underline">
                            + Ajukan Tukar Jadwal
                        </button>
                    </div>

                    @if ($mySchedule->isEmpty())
                        <div class="py-12 text-center text-slate-500 text-sm">
                            Belum ada jadwal piket yang ditetapkan untuk Anda pada periode ini.
                        </div>
                    @else
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($mySchedule as $item)
                                <div class="p-4 rounded-xl border {{ $item['is_active_duty'] ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50' }} space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                            {{ \Carbon\Carbon::parse($item['duty_date'])->translatedFormat('l') }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $item['badge_class'] }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </div>
                                    <div class="text-lg font-bold text-slate-800">
                                        {{ \Carbon\Carbon::parse($item['duty_date'])->translatedFormat('d F Y') }}
                                    </div>

                                    @if ($item['type'] === 'swapped_out')
                                        <div class="text-xs text-amber-800 bg-amber-50 p-2 rounded-lg border border-amber-200">
                                            Digantikan oleh: <strong>{{ $item['partner_name'] }}</strong><br>
                                            <span class="italic text-[11px]">Alasan: "{{ $item['reason'] }}"</span>
                                        </div>
                                    @elseif ($item['type'] === 'swapped_in')
                                        <div class="text-xs text-blue-800 bg-blue-50 p-2 rounded-lg border border-blue-200">
                                            Menggantikan: <strong>{{ $item['partner_name'] }}</strong><br>
                                            <span class="italic text-[11px]">Alasan: "{{ $item['reason'] }}"</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-emerald-700 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Bertugas melayani di lobi PST BPS
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: TUKAR JADWAL (SWAP PIKET)           -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'swap'" x-transition class="space-y-6" style="display: none;">
                <!-- Form Pengajuan Swap -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-2xl">
                    <div class="border-b border-slate-200 pb-4 mb-6">
                        <h3 class="font-bold text-slate-800 text-lg">Ajukan Pertukaran Jadwal Piket (Swap)</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Sesuai aturan operasional: jadwal asli tidak pernah ditimpa. Riwayat pertukaran akan tersimpan dan otomatis memperbarui penugasan harian.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('petugas.swap.store') }}" class="space-y-5">
                        @csrf

                        <!-- Pilihan Tanggal Piket Asli Petugas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                1. Tanggal Piket Anda yang Ingin Ditukar <span class="text-rose-500">*</span>
                            </label>
                            @if ($upcomingDutyDates->isEmpty() && !Auth::user()->isAdmin())
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs">
                                    Anda tidak memiliki jadwal piket asli mendatang yang terdaftar di sistem.
                                </div>
                            @else
                                <select name="duty_date" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">-- Pilih Tanggal Piket Anda --</option>
                                    @foreach ($upcomingDutyDates as $schedule)
                                        <option value="{{ $schedule->duty_date->format('Y-m-d') }}" {{ old('duty_date') == $schedule->duty_date->format('Y-m-d') ? 'selected' : '' }}>
                                            {{ $schedule->duty_date->translatedFormat('l, d F Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <!-- Pilihan Petugas Pengganti -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                2. Petugas Pengganti <span class="text-rose-500">*</span>
                            </label>
                            <select name="replacement_officer_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">-- Pilih Petugas Rekan --</option>
                                @foreach ($availableOfficers as $officer)
                                    <option value="{{ $officer->id }}" {{ old('replacement_officer_id') == $officer->id ? 'selected' : '' }}>
                                        {{ $officer->name }} ({{ $officer->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Alasan Swap -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                3. Alasan Pertukaran <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="reason" rows="3" required
                                placeholder="Contoh: Bertugas dinas luar / survei lapangan / keperluan penting lainnya..."
                                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('reason') }}</textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 bg-blue-900 hover:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span>Simpan Pertukaran Jadwal</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Riwayat Swap Terkait Petugas Ini -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h4 class="font-bold text-slate-800 text-sm">Riwayat Pertukaran Jadwal (Swap Log)</h4>
                    </div>

                    @if ($swapHistory->isEmpty())
                        <div class="p-8 text-center text-slate-400 text-xs">
                            Belum ada riwayat pertukaran jadwal.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-semibold">
                                    <tr>
                                        <th class="px-4 py-3">Tanggal Piket</th>
                                        <th class="px-4 py-3">Petugas Asli</th>
                                        <th class="px-4 py-3">Petugas Pengganti</th>
                                        <th class="px-4 py-3">Alasan</th>
                                        <th class="px-4 py-3">Waktu Swap</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($swapHistory as $swap)
                                        <tr class="hover:bg-slate-50 text-xs">
                                            <td class="px-4 py-3 font-semibold text-slate-800">
                                                {{ $swap->duty_date->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ $swap->originalOfficer?->name }}
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-blue-700">
                                                {{ $swap->replacementOfficer?->name }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate">
                                                {{ $swap->reason }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-400">
                                                {{ $swap->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: PREVIEW FOTO TAMU UKURAN BESAR      -->
            <!-- ========================================== -->
            <div x-show="activePhotoModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activePhotoModal = null">
                <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 relative">
                    <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="font-bold text-slate-800 text-sm">Foto Tamu (Mode Privat)</h4>
                        <button type="button" @click="activePhotoModal = null" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 bg-slate-100 flex items-center justify-center">
                        <img :src="activePhotoModal" alt="Foto Tamu" class="rounded-xl max-h-[70vh] object-contain shadow-md" />
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: SELESAI LAYANI + CATATAN LAYANAN    -->
            <!-- ========================================== -->
            <div x-show="activeFinishModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activeFinishModal = null">
                <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200">
                    <form method="POST" :action="'/petugas/kunjungan/' + activeFinishModal + '/finish'">
                        @csrf
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-base">Selesaikan Layanan Tamu</h4>
                                    <p class="text-xs text-slate-500" x-text="'Nomor: ' + (selectedVisit ? selectedVisit.queue_number : '') + ' • ' + (selectedVisit ? selectedVisit.full_name : '')"></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Catatan Hasil Layanan (Opsional)
                                </label>
                                <textarea name="officer_note" rows="4"
                                    placeholder="Contoh: Tamu telah diberikan data inflasi kab. Inhil 2025 dan rekomendasi metodologi survei..."
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                                <p class="text-[11px] text-slate-400 mt-1">Catatan ini akan tersimpan sebagai bukti pelayanan dan laporan bulanan.</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                            <button type="button" @click="activeFinishModal = null"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-lg">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                                Konfirmasi Selesai
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: TINDAK LANJUT ONLINE                -->
            <!-- ========================================== -->
            <div x-show="activeOnlineModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activeOnlineModal = null">
                <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200">
                    <form method="POST" :action="'/petugas/online/' + activeOnlineModal + '/status'">
                        @csrf
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                                    🌐
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-base">Tindak Lanjut Permohonan Online</h4>
                                    <p class="text-xs text-slate-500" x-text="(selectedVisit ? selectedVisit.ticket_code : '') + ' • ' + (selectedVisit ? selectedVisit.full_name : '')"></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Ubah Status Permohonan
                                </label>
                                <select name="status" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="dilayani" :selected="selectedVisit && selectedVisit.status === 'dilayani'">Sedang Dilayani</option>
                                    <option value="selesai" :selected="selectedVisit && selectedVisit.status === 'selesai'">Selesai</option>
                                    <option value="dibatalkan" :selected="selectedVisit && selectedVisit.status === 'dibatalkan'">Dibatalkan</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Catatan Tindak Lanjut
                                </label>
                                <textarea name="officer_note" rows="3"
                                    x-text="selectedVisit ? selectedVisit.officer_note : ''"
                                    placeholder="Contoh: Sudah dikirim via email / WhatsApp pada jam 14:00..."
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                            <button type="button" @click="activeOnlineModal = null"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-lg">
                                Tutup
                            </button>
                            <button type="submit"
                                class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: BATAL / TIDAK HADIR                 -->
            <!-- ========================================== -->
            <div x-show="activeCancelModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activeCancelModal = null">
                <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200">
                    <form method="POST" :action="'/petugas/kunjungan/' + activeCancelModal + '/status'">
                        @csrf
                        <div class="p-6 space-y-4">
                            <h4 class="font-bold text-slate-900 text-base">Ubah Status Antrean</h4>
                            <p class="text-xs text-slate-500" x-text="'Nomor: ' + (selectedVisit ? selectedVisit.queue_number : '') + ' • ' + (selectedVisit ? selectedVisit.full_name : '')"></p>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Status</label>
                                <select name="status" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 text-sm">
                                    <option value="tidak_hadir">Tidak Hadir (Tamu dilewati)</option>
                                    <option value="dibatalkan">Dibatalkan</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Alasan (Opsional)</label>
                                <textarea name="officer_note" rows="2" placeholder="Alasan pembatalan..." class="w-full rounded-xl border-slate-300 shadow-sm text-sm"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                            <button type="button" @click="activeCancelModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
