<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Kelola Jadwal Piket Petugas PST') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Atur jadwal piket lobi harian atau terapkan rotasi massal satu bulan penuh (FR-32).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="showMassModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Penugasan Massal (1 Bulan)</span>
                </button>

                <button type="button" @click="showAddModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <span>+ Tambah Harian</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ showMassModal: false, showAddModal: false }">
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

            <!-- Pemilih Bulan & Tahun -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" action="{{ route('admin.schedules.index') }}" class="flex items-center gap-3">
                    <div>
                        <select name="month" class="text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <select name="year" class="text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                        Tampilkan
                    </button>
                </form>

                <div class="text-xs text-slate-500">
                    Periode: <strong>{{ $startDate->translatedFormat('F Y') }}</strong> ({{ $schedules->flatten()->count() }} jadwal aktif)
                </div>
            </div>

            <!-- Tabel / Grid Jadwal Piket Kalender -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3.5 w-36">Hari & Tanggal</th>
                                <th class="px-4 py-3.5">Petugas Piket Terjadwal</th>
                                <th class="px-4 py-3.5 text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @for ($day = 1; $day <= $startDate->daysInMonth; $day++)
                                @php
                                    $currentDate = $startDate->copy()->day($day);
                                    $dateStr = $currentDate->toDateString();
                                    $isWeekend = $currentDate->isWeekend();
                                    $daySchedules = $schedules->get($dateStr, collect());
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors {{ $isWeekend ? 'bg-slate-50/50 opacity-60' : '' }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-xs {{ $currentDate->isToday() ? 'text-blue-700' : 'text-slate-800' }}">
                                            {{ $currentDate->translatedFormat('l') }}
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono">
                                            {{ $currentDate->translatedFormat('d M Y') }}
                                            @if ($currentDate->isToday())
                                                <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-800 font-sans font-bold">Hari Ini</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($daySchedules->isEmpty())
                                            @if ($isWeekend)
                                                <span class="text-xs text-slate-400 italic">Libur Akhir Pekan</span>
                                            @else
                                                <span class="text-xs text-rose-500 italic">Belum Ada Petugas</span>
                                            @endif
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($daySchedules as $sch)
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-semibold">
                                                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                                        <span>{{ $sch->officer?->name }}</span>
                                                        <form method="POST" action="{{ route('admin.schedules.destroy', $sch) }}" class="inline" onsubmit="return confirm('Hapus jadwal piket petugas ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-slate-400 hover:text-rose-600 ml-1">✕</button>
                                                        </form>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        @if (! $isWeekend)
                                            <button type="button"
                                                @click="showAddModal = true; $nextTick(() => { document.getElementById('add_duty_date').value = '{{ $dateStr }}'; })"
                                                class="px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50 rounded-lg transition">
                                                + Piket
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: Tambah Jadwal Harian -->
            <div x-show="showAddModal" x-cloak
                class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showAddModal = false">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Tambah Jadwal Piket Harian</h3>

                    <form method="POST" action="{{ route('admin.schedules.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Piket</label>
                            <input type="date" id="add_duty_date" name="duty_date" required
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pilih Petugas</label>
                            <select name="officer_id" required class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Pilih Petugas --</option>
                                @foreach ($officers as $off)
                                    <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->role }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm">
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Penugasan Massal 1 Bulan -->
            <div x-show="showMassModal" x-cloak
                class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showMassModal = false">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Penugasan Massal Bulanan (Rotasi Otomatis)</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Sistem akan membagi piket secara bergiliran (round-robin) untuk semua hari kerja (Senin s.d. Jumat).
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.schedules.mass_assign') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bulan</label>
                                <select name="month" class="w-full text-xs rounded-xl border-slate-300">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tahun</label>
                                <select name="year" class="w-full text-xs rounded-xl border-slate-300">
                                    @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-2">
                                Pilih Petugas yang Diikutsertakan Rotasi:
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto p-3 bg-slate-50 rounded-xl border border-slate-200">
                                @foreach ($officers as $off)
                                    <label class="flex items-center gap-2 text-xs font-medium text-slate-800 cursor-pointer">
                                        <input type="checkbox" name="officer_ids[]" value="{{ $off->id }}" checked
                                            class="rounded border-slate-300 text-blue-900 focus:ring-blue-500">
                                        <span>{{ $off->name }}</span>
                                        <span class="text-slate-400 font-mono text-[11px]">({{ $off->username }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-xs text-blue-900 flex items-center gap-2">
                            <input type="checkbox" name="overwrite" value="1" id="overwrite_cb" class="rounded border-blue-300 text-blue-900">
                            <label for="overwrite_cb" class="cursor-pointer">
                                Timpa (overwrite) jadwal yang sudah ada pada bulan tersebut.
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="showMassModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm">
                                Terapkan Rotasi Massal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
