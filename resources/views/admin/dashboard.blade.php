<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Dasbor Administrator') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                Peran: Admin
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center font-bold text-xl">
                        A
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Selamat datang, {{ Auth::user()->name }}!</h3>
                        <p class="text-sm text-slate-500">Anda masuk sebagai Administrator BPS Kab. Indragiri Hilir.</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status Fondasi (Fase 1)</div>
                        <div class="text-lg font-bold text-emerald-600 mt-1">Aktif & Siap</div>
                        <div class="text-xs text-slate-500 mt-1">Sistem autentikasi & migrasi berhasil dibangun.</div>
                    </div>
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Akses Administrator</div>
                        <div class="text-lg font-bold text-slate-800 mt-1">Penuh</div>
                        <div class="text-xs text-slate-500 mt-1">Manajemen pengguna, jadwal piket, ekspor laporan.</div>
                    </div>
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Zona Waktu Server</div>
                        <div class="text-lg font-bold text-slate-800 mt-1">{{ config('app.timezone') }}</div>
                        <div class="text-xs text-slate-500 mt-1">Waktu: {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
