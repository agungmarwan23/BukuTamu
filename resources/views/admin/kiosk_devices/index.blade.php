<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Kelola Perangkat Kios Lobi Terdaftar') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Daftarkan PC kios lobi dan dapatkan tautan aktivasi berbasis cookie aman (FR-01).
                </p>
            </div>
            <div>
                <button type="button" @click="showAddModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <span>+ Daftarkan Kios Baru</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ showAddModal: false }">
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

            <!-- Modal / Card Tautan Aktivasi Baru Dibuat -->
            @if (session('activation_url'))
                <div class="p-6 rounded-2xl bg-blue-50 border-2 border-blue-300 shadow-md space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-900 text-white flex items-center justify-center font-bold">
                            🔑
                        </div>
                        <div>
                            <h4 class="font-bold text-blue-950 text-base">Tautan Aktivasi Perangkat Berhasil Dibuat!</h4>
                            <p class="text-xs text-blue-700">Perangkat: <strong>{{ session('device_name') }}</strong></p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600">
                        Buka tautan ini <strong>sekali saja langsung di browser PC Kios Lobi</strong> untuk mendaftarkan dan menyimpan cookie otentikasi perangkat:
                    </p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ session('activation_url') }}" id="act_url"
                            class="w-full text-xs font-mono bg-white rounded-xl border-blue-200 p-2.5 text-blue-900 select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('act_url').value); alert('Tautan aktivasi berhasil disalin!');"
                            class="px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shrink-0">
                            Salin URL
                        </button>
                    </div>
                </div>
            @endif

            <!-- Daftar Perangkat -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Daftar Perangkat PC Kios</span>
                </div>

                @if ($devices->isEmpty())
                    <div class="p-12 text-center text-xs text-slate-400">
                        Belum ada perangkat kios yang didaftarkan.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-100/70 text-slate-600 uppercase text-xs font-semibold">
                                <tr>
                                    <th class="px-4 py-3.5">Nama Perangkat</th>
                                    <th class="px-4 py-3.5">Token Hash</th>
                                    <th class="px-4 py-3.5">Diaktivasi Oleh</th>
                                    <th class="px-4 py-3.5">Terakhir Aktif</th>
                                    <th class="px-4 py-3.5 text-center">Status</th>
                                    <th class="px-4 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-xs">
                                @foreach ($devices as $dev)
                                    <tr class="hover:bg-slate-50/70 transition {{ ! $dev->is_active ? 'bg-slate-50 opacity-60' : '' }}">
                                        <td class="px-4 py-3.5 font-bold text-slate-900">
                                            {{ $dev->name }}
                                        </td>
                                        <td class="px-4 py-3.5 font-mono text-slate-400 text-[11px] max-w-xs truncate">
                                            {{ substr($dev->token_hash, 0, 16) }}...
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-600">
                                            {{ $dev->activator?->name ?: 'Belum diaktivasi' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-500">
                                            {{ $dev->last_seen_at ? $dev->last_seen_at->diffForHumans() : '-' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($dev->is_active)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" action="{{ route('admin.kiosks.toggle', $dev) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $dev->is_active ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                                        {{ $dev->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.kiosks.destroy', $dev) }}" class="inline" onsubmit="return confirm('Hapus perangkat ini secara permanen?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- MODAL: Tambah Kios Baru -->
            <div x-show="showAddModal" x-cloak
                class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showAddModal = false">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Daftarkan Perangkat Kios Lobi</h3>
                    <p class="text-xs text-slate-500">
                        Token unik akan otomatis digenerate dan dapat diaktifkan pada browser perangkat kios.
                    </p>

                    <form method="POST" action="{{ route('admin.kiosks.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Perangkat Kios <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="Contoh: PC Kios Lobi 1"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm">
                                Buat Token Aktivasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
