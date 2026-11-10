<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Daftar Kunjungan Tamu (Audit & Rekap)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Semua riwayat tamu lobi (kios) dan permohonan daring (online) dengan filter komprehensif.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.visits.template') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold shadow-sm transition-colors"
                    title="Unduh contoh template CSV kunjungan">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Template CSV</span>
                </a>

                <button type="button" @click="$dispatch('open-import-visit-modal')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span>Impor Riwayat CSV</span>
                </button>

                <a href="{{ route('admin.visits.export', request()->query()) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Ekspor CSV / Excel</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ activePhotoModal: null, showImportModal: false }" @open-import-visit-modal.window="showImportModal = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Filter Panel -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <form method="GET" action="{{ route('admin.visits.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                        <!-- Rentang Tanggal Mulai -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Rentang Tanggal Selesai -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Saluran -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Saluran</label>
                            <select name="channel" class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Saluran</option>
                                <option value="kiosk" {{ request('channel') === 'kiosk' ? 'selected' : '' }}>Kios Lobi</option>
                                <option value="online" {{ request('channel') === 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                        </div>

                        <!-- Sumber Data (Langsung vs Impor) -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Sumber Data</label>
                            <select name="is_imported" class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Sumber</option>
                                <option value="0" {{ request('is_imported') === '0' ? 'selected' : '' }}>Kunjungan Langsung</option>
                                <option value="1" {{ request('is_imported') === '1' ? 'selected' : '' }}>Data Impor (Riwayat)</option>
                            </select>
                        </div>

                        <!-- Tujuan Layanan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Tujuan</label>
                            <select name="purpose" class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Tujuan</option>
                                <option value="pst" {{ request('purpose') === 'pst' ? 'selected' : '' }}>Layanan PST</option>
                                <option value="non_pst" {{ request('purpose') === 'non_pst' ? 'selected' : '' }}>Non-PST</option>
                                <option value="pengaduan" {{ request('purpose') === 'pengaduan' ? 'selected' : '' }}>Pengaduan</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Status</label>
                            <select name="status" class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Status</option>
                                <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                                <option value="dipanggil" {{ request('status') === 'dipanggil' ? 'selected' : '' }}>Dipanggil</option>
                                <option value="dilayani" {{ request('status') === 'dilayani' ? 'selected' : '' }}>Dilayani</option>
                                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="tidak_hadir" {{ request('status') === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Petugas -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Petugas</label>
                            <select name="officer_id" class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Petugas</option>
                                @foreach ($officers as $off)
                                    <option value="{{ $off->id }}" {{ request('officer_id') == $off->id ? 'selected' : '' }}>
                                        {{ $off->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-slate-100">
                        <!-- Pencarian Kata Kunci -->
                        <div class="relative flex-1 max-w-md">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari nama tamu, instansi, kode tiket, nomor HP..."
                                class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.visits.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-lg">
                                Reset Filter
                            </a>
                            <button type="submit" class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm transition-colors">
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Daftar Kunjungan -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Ditemukan {{ $visits->total() }} riwayat kunjungan</span>
                </div>

                @if ($visits->isEmpty())
                    <div class="p-12 text-center text-slate-500 text-sm">
                        Tidak ada data kunjungan yang cocok dengan filter yang dipilih.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-100/70 text-slate-600 uppercase text-xs font-semibold">
                                <tr>
                                    <th class="px-4 py-3.5">Kode / Tiket</th>
                                    <th class="px-4 py-3.5">Tanggal</th>
                                    <th class="px-4 py-3.5">Foto</th>
                                    <th class="px-4 py-3.5">Tamu & Instansi</th>
                                    <th class="px-4 py-3.5">Keperluan & Layanan</th>
                                    <th class="px-4 py-3.5">Petugas</th>
                                    <th class="px-4 py-3.5 text-center">Status</th>
                                    <th class="px-4 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($visits as $v)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if ($v->queue_number)
                                                <span class="inline-block px-2.5 py-1 rounded-lg bg-blue-900 text-white font-mono font-bold text-xs">
                                                    {{ $v->queue_number }}
                                                </span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-xs border border-slate-200">
                                                    {{ $v->is_imported ? 'Impor' : 'Online' }}
                                                </span>
                                            @endif
                                            <div class="text-[11px] font-mono text-slate-400 mt-1 flex items-center gap-1 flex-wrap">
                                                <span>{{ $v->ticket_code }}</span>
                                                @if ($v->is_imported)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Sumber: {{ $v->import_source ?: 'Spreadsheet Lama' }}">
                                                        Spreadsheet
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                            {{ $v->visit_date ? $v->visit_date->format('d/m/Y') : '-' }}
                                            <div class="text-[11px] text-slate-400">{{ $v->created_at ? $v->created_at->format('H:i') . ' WIB' : '-' }}</div>
                                        </td>

                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if ($v->photo_path)
                                                <img src="{{ route('visit.photo', $v) }}"
                                                    alt="Foto"
                                                    @click="activePhotoModal = '{{ route('visit.photo', $v) }}'"
                                                    class="w-10 h-10 rounded-lg object-cover border border-slate-200 cursor-pointer shadow-sm hover:scale-105 transition-transform" />
                                            @else
                                                <span class="text-[11px] text-slate-400">-</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $v->full_name }}</div>
                                            <div class="text-xs text-slate-500">{{ $v->institution ?: 'Masyarakat Umum' }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $v->phone }}</div>
                                        </td>

                                        <td class="px-4 py-3.5 max-w-xs">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase {{ $v->purpose === 'pst' ? 'bg-blue-100 text-blue-800' : ($v->purpose === 'pengaduan' ? 'bg-rose-100 text-rose-800' : 'bg-purple-100 text-purple-800') }}">
                                                {{ $v->purpose === 'pst' ? 'PST' : ($v->purpose === 'pengaduan' ? 'Aduan' : 'Non-PST') }}
                                            </span>
                                            <div class="text-xs text-slate-700 mt-0.5 truncate">{{ $v->service_type ?: $v->detail }}</div>
                                        </td>

                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-700">
                                            {{ $v->assignedOfficer?->name ?: '-' }}
                                        </td>

                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $v->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : ($v->status === 'dilayani' ? 'bg-indigo-100 text-indigo-800' : ($v->status === 'dipanggil' ? 'bg-blue-100 text-blue-800' : ($v->status === 'tidak_hadir' ? 'bg-slate-100 text-slate-600' : ($v->status === 'dibatalkan' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800')))) }}">
                                                {{ ucfirst(str_replace('_', ' ', $v->status)) }}
                                            </span>
                                            @if ($v->visit_date && $v->visit_date->lt(now()->startOfDay()) && in_array($v->status, ['menunggu', 'dipanggil', 'dilayani']))
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                        ⚠ Belum Selesai
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <a href="{{ route('admin.visits.show', $v) }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg {{ $v->visit_date && $v->visit_date->lt(now()->startOfDay()) && in_array($v->status, ['menunggu', 'dipanggil', 'dilayani']) ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }} text-xs font-bold transition-colors">
                                                <span>{{ $v->visit_date && $v->visit_date->lt(now()->startOfDay()) && in_array($v->status, ['menunggu', 'dipanggil', 'dilayani']) ? 'Kelola' : 'Detail' }}</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $visits->links() }}
                    </div>
                @endif
            </div>

            <!-- Lightbox Modal Foto -->
            <div x-show="activePhotoModal"
                x-cloak
                class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activePhotoModal = null">
                <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl relative">
                    <div class="p-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-700">Foto Tamu Privat</span>
                        <button type="button" @click="activePhotoModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-4 bg-slate-100 flex items-center justify-center">
                        <img :src="activePhotoModal" alt="Foto Tamu" class="rounded-xl max-h-[70vh] object-contain shadow" />
                    </div>
                </div>
            <!-- Modal Impor Riwayat Kunjungan CSV -->
            <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div @click.away="showImportModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <span>Impor Riwayat Kunjungan / Spreadsheet</span>
                        </h3>
                        <button type="button" @click="showImportModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-900 space-y-1.5">
                        <p class="font-semibold">Format Kolom File CSV:</p>
                        <p class="text-[11px] leading-relaxed">
                            File CSV mendukung kolom: <code class="bg-white px-1 py-0.5 rounded border border-indigo-200 font-mono">tanggal,nama_lengkap,instansi,no_hp,email,tujuan,jenis_layanan,rincian_keperluan,status</code>
                        </p>
                        <p class="text-[11px] text-slate-600">
                            * Kolom wajib: <strong>nama_lengkap</strong>. Tanggal dapat berupa YYYY-MM-DD atau DD/MM/YYYY.
                        </p>
                        <div class="pt-1">
                            <a href="{{ route('admin.visits.template') }}" class="inline-flex items-center gap-1 text-indigo-700 font-bold hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Unduh Contoh Template CSV Kunjungan</span>
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.visits.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih File CSV (.csv / .txt):</label>
                            <input type="file" name="csv_file" accept=".csv,.txt" required
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl p-1 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Label / Nama Sumber Data:</label>
                            <input type="text" name="source_name" placeholder="Misal: Spreadsheet Buku Tamu 2025" value="Impor Spreadsheet Lama"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-[11px] text-slate-400 mt-1">Label ini akan disimpan sebagai tanda pembeda data riwayat lama.</p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="showImportModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm">
                                Unggah & Impor Riwayat
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
