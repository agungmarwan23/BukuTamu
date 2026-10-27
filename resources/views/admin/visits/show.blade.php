<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.visits.index') }}" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 shadow-sm transition">
                    &larr;
                </a>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">
                        Detail Kunjungan &bull; {{ $visit->ticket_code }}
                    </h2>
                    <p class="text-xs text-slate-500">
                        {{ $visit->created_at->translatedFormat('l, d F Y &bull; H:i:s') }} WIB
                    </p>
                </div>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                    {{ $visit->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : ($visit->status === 'dilayani' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') }}">
                    Status: {{ str_replace('_', ' ', $visit->status) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Kolom Kiri: Profil Tamu & Foto -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                    <div class="text-center">
                        @if ($visit->photo_path)
                            <img src="{{ route('visit.photo', $visit) }}" alt="{{ $visit->full_name }}"
                                class="w-28 h-28 mx-auto rounded-2xl object-cover border-2 border-blue-900/10 shadow-md mb-3" />
                        @else
                            <div class="w-24 h-24 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-xs mb-3">
                                Tanpa Foto
                            </div>
                        @endif

                        <h3 class="font-bold text-slate-900 text-lg">{{ $visit->full_name }}</h3>
                        <p class="text-xs text-slate-500">{{ $visit->institution ?: 'Masyarakat Umum' }}</p>

                        @if ($visit->queue_number)
                            <div class="mt-3 inline-block px-4 py-1.5 rounded-xl bg-blue-900 text-white font-mono font-black text-lg shadow-sm">
                                {{ $visit->queue_number }}
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-slate-100 pt-4 space-y-3 text-xs">
                        <div>
                            <span class="text-slate-400 block">Nomor HP / WhatsApp:</span>
                            <span class="font-semibold text-slate-800">{{ $visit->phone }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Email:</span>
                            <span class="font-semibold text-slate-800">{{ $visit->email ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Saluran Masuk:</span>
                            <span class="font-semibold uppercase {{ $visit->channel === 'kiosk' ? 'text-blue-700' : 'text-purple-700' }}">
                                {{ $visit->channel === 'kiosk' ? 'Kios Lobi Kantor' : 'Permohonan Online' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Persetujuan UU PDP (Data Pribadi):</span>
                            <span class="text-emerald-700 font-semibold">
                                ✓ Disetujui ({{ $visit->consent_at ? $visit->consent_at->format('d/m/Y H:i') : '-' }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kolom Tengah & Kanan: Rincian Keperluan, Petugas, Timeline -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Kartu Rincian Layanan -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h4 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-3">Rincian Permohonan & Keperluan</h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block">Tujuan Layanan:</span>
                                <span class="font-bold text-slate-800 uppercase">{{ strtoupper(str_replace('_', ' ', $visit->purpose)) }}</span>
                            </div>
                            @if ($visit->service_type)
                                <div>
                                    <span class="text-slate-400 block">Jenis Layanan PST:</span>
                                    <span class="font-bold text-slate-800">{{ $visit->service_type }}</span>
                                </div>
                            @endif
                            @if ($visit->non_pst_type)
                                <div>
                                    <span class="text-slate-400 block">Kategori Non-PST:</span>
                                    <span class="font-bold text-slate-800">{{ $visit->non_pst_type }}</span>
                                </div>
                            @endif
                            @if ($visit->complaint_title)
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 block">Judul Pengaduan:</span>
                                    <span class="font-bold text-rose-800">{{ $visit->complaint_title }}</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="text-xs text-slate-400 block mb-1">Rincian Keperluan:</span>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {{ $visit->detail }}
                            </div>
                        </div>

                        <!-- Berkas Lampiran -->
                        @if ($visit->attachments->isNotEmpty())
                            <div>
                                <span class="text-xs text-slate-400 block mb-2">Berkas Lampiran Pendukung:</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($visit->attachments as $att)
                                        <a href="{{ route('visit.attachment', $att) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-blue-700 transition">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>{{ $att->original_name }} ({{ round($att->size / 1024) }} KB)</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Petugas & Catatan Layanan -->
                        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block">Petugas yang Melayani:</span>
                                <span class="font-bold text-slate-800">{{ $visit->assignedOfficer?->name ?: 'Belum Ditugaskan' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Durasi Layanan:</span>
                                <span class="font-semibold text-slate-700">
                                    @if ($visit->started_at && $visit->finished_at)
                                        {{ $visit->started_at->diffInMinutes($visit->finished_at) }} menit
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            @if ($visit->officer_note)
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 block mb-1">Catatan Layanan Petugas:</span>
                                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs italic">
                                        "{{ $visit->officer_note }}"
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Riwayat Status & Jejak Audit (Audit Trail) -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <h4 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-3">Jejak Audit Status (Audit Trail)</h4>

                        @if ($visit->statusLogs->isEmpty())
                            <div class="text-xs text-slate-400">Belum ada catatan transisi status.</div>
                        @else
                            <ol class="relative border-l border-slate-200 ml-3 space-y-4 text-xs">
                                @foreach ($visit->statusLogs as $log)
                                    <li class="mb-2 ml-4">
                                        <div class="absolute w-3 h-3 bg-blue-900 rounded-full mt-1.5 -left-1.5 border border-white"></div>
                                        <time class="mb-1 font-mono text-[11px] font-normal leading-none text-slate-400">
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </time>
                                        <h5 class="text-xs font-semibold text-slate-800">
                                            Status berubah dari
                                            <span class="font-mono font-bold">{{ $log->from_status ?: 'BARU' }}</span>
                                            &rarr;
                                            <span class="font-mono font-bold text-blue-700">{{ $log->to_status }}</span>
                                        </h5>
                                        <p class="text-[11px] text-slate-500">
                                            Oleh: {{ $log->user?->name ?: 'Sistem Registrasi' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
