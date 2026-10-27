<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Meja Resepsionis — Pemanggilan Antrean') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Peran: Resepsionis
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center font-bold text-xl">
                        R
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Selamat datang, {{ Auth::user()->name }}!</h3>
                        <p class="text-sm text-slate-500">Anda masuk sebagai Resepsionis BPS Kab. Indragiri Hilir.</p>
                    </div>
                </div>

                <div class="mt-6 p-4 rounded-lg bg-blue-50 border border-blue-100 text-blue-900 text-sm">
                    Fitur pemanggilan antrean (Panggil, Ulang, Lewati) dan integrasi TV layar lobi akan diaktifkan pada <strong>Fase 3</strong>.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
