<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Analitik Pertukaran Jadwal Piket (Duty Swaps)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Statistik frekuensi pergantian piket: siapa paling sering digantikan dan menggantikan (FR-33).
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Rentang Waktu -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" action="{{ route('admin.analytics.swaps') }}" class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Periode:</span>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs rounded-xl border-slate-300">
                        <span>s.d.</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs rounded-xl border-slate-300">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-semibold shadow-sm">
                        Filter
                    </button>
                </form>

                <div class="text-xs font-bold text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    Total Swap Terjadi: <span class="font-mono text-sm text-blue-700 font-black">{{ $totalSwaps }}</span> kali
                </div>
            </div>

            <!-- Kartu Ranking: Paling Sering Digantikan vs Menggantikan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- 1. Paling Sering Digantikan (Original Officer) -->
                <div class="bg-white p-6 rounded-2xl border border-amber-200 bg-amber-50/10 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-amber-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Paling Sering Digantikan</h3>
                            <p class="text-xs text-slate-500">Petugas yang jadwal piket aslinya ditukar ke rekan lain.</p>
                        </div>
                        <span class="p-2 bg-amber-100 text-amber-800 rounded-xl font-bold text-xs">Swap Out</span>
                    </div>

                    @if ($mostSwappedOut->isEmpty())
                        <div class="py-8 text-center text-xs text-slate-400">Tidak ada data swap pada periode ini.</div>
                    @else
                        <ul class="divide-y divide-slate-100 mt-2">
                            @foreach ($mostSwappedOut as $idx => $item)
                                <li class="py-3 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-[11px]">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $item->originalOfficer?->name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">{{ $item->originalOfficer?->username }}</div>
                                        </div>
                                    </div>
                                    <span class="font-mono font-black text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-xl">
                                        {{ $item->total }} kali
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- 2. Paling Sering Menggantikan (Replacement Officer) -->
                <div class="bg-white p-6 rounded-2xl border border-blue-200 bg-blue-50/10 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-blue-100">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Paling Sering Menggantikan</h3>
                            <p class="text-xs text-slate-500">Petugas rekan yang bersedia mengambil alih tugas piket.</p>
                        </div>
                        <span class="p-2 bg-blue-100 text-blue-800 rounded-xl font-bold text-xs">Swap In</span>
                    </div>

                    @if ($mostSwappedIn->isEmpty())
                        <div class="py-8 text-center text-xs text-slate-400">Tidak ada data swap pada periode ini.</div>
                    @else
                        <ul class="divide-y divide-slate-100 mt-2">
                            @foreach ($mostSwappedIn as $idx => $item)
                                <li class="py-3 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-[11px]">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $item->replacementOfficer?->name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">{{ $item->replacementOfficer?->username }}</div>
                                        </div>
                                    </div>
                                    <span class="font-mono font-black text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-xl">
                                        {{ $item->total }} kali
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            </div>

            <!-- Tabel Riwayat Swap Lengkap -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="font-bold text-slate-800 text-sm">Riwayat Lengkap Pertukaran Piket (Log Duty Swaps)</h3>
                </div>

                @if ($swaps->isEmpty())
                    <div class="p-12 text-center text-xs text-slate-400">Belum ada riwayat pertukaran piket pada rentang tanggal ini.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-100/70 text-slate-600 uppercase text-xs font-semibold">
                                <tr>
                                    <th class="px-4 py-3">Tanggal Piket</th>
                                    <th class="px-4 py-3">Petugas Asli</th>
                                    <th class="px-4 py-3">Petugas Pengganti</th>
                                    <th class="px-4 py-3">Alasan Pertukaran</th>
                                    <th class="px-4 py-3">Diajukan Oleh</th>
                                    <th class="px-4 py-3 text-right">Waktu Swap</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-xs">
                                @foreach ($swaps as $s)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-4 py-3.5 font-bold text-slate-800 whitespace-nowrap">
                                            {{ $s->duty_date->translatedFormat('d F Y') }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="text-amber-800 font-semibold">{{ $s->originalOfficer?->name }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="text-blue-800 font-bold">{{ $s->replacementOfficer?->name }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 max-w-sm text-slate-700 italic">
                                            "{{ $s->reason }}"
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap">
                                            {{ $s->swappedBy?->name ?: 'Admin' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-slate-400 font-mono whitespace-nowrap">
                                            {{ $s->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $swaps->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
