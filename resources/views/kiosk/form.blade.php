<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kios Buku Tamu Lobi &bull; BPS Kab. Indragiri Hilir</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-slate-800 min-h-screen flex flex-col justify-between select-none">
    <!-- Header Kios -->
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-white/10 px-6 py-4 flex items-center justify-between text-white">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-amber-400 text-xl shadow-lg">
                BPS
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-white leading-tight">KIOS BUKU TAMU DIGITAL</h1>
                <p class="text-xs font-semibold text-amber-400 tracking-wider">PELAYANAN STATISTIK TERPADU &bull; BPS KAB. INDRAGIRI HILIR</p>
            </div>
        </div>

        <div class="text-right hidden sm:block">
            <div class="text-sm font-semibold text-slate-200" id="live-clock">--:--:-- WIB</div>
            <div class="text-xs text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="w-full max-w-4xl mx-auto px-4 py-6 flex-grow flex flex-col justify-center"
          x-data="kioskForm()"
          x-cloak>

        <!-- Notification Banner -->
        @if (session('status'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/40 text-red-200 text-sm">
                <div class="font-bold mb-1">Periksa isian form Anda:</div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col">
            <!-- Progress Bar -->
            <div class="bg-slate-50 border-b border-slate-100 px-6 py-4">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <div class="flex items-center gap-2" :class="step >= 1 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 1 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">1</span>
                        <span>Identitas Tamu</span>
                    </div>
                    <div class="h-0.5 flex-1 mx-3" :class="step >= 2 ? 'bg-blue-900' : 'bg-slate-200'"></div>

                    <div class="flex items-center gap-2" :class="step >= 2 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 2 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">2</span>
                        <span>Rincian Layanan</span>
                    </div>
                    <div class="h-0.5 flex-1 mx-3" :class="step >= 3 ? 'bg-blue-900' : 'bg-slate-200'"></div>

                    <div class="flex items-center gap-2" :class="step >= 3 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 3 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">3</span>
                        <span>Foto & Konfirmasi</span>
                    </div>
                </div>
            </div>

            <!-- Form Body -->
            <form action="{{ route('kios.store') }}" method="POST" enctype="multipart/form-data" id="kiosk-visit-form" class="p-6 sm:p-8 flex-grow">
                @csrf
                <input type="hidden" name="channel" value="kiosk">
                <!-- Honeypot Anti-Bot -->
                <input type="text" name="website_url" value="" class="hidden" tabindex="-1" autocomplete="off">
                <!-- Base64 Photo Hidden Input -->
                <input type="hidden" name="photo" :value="photoData" id="photo-input">

                <!-- LANGKAH 1: IDENTITAS TAMU -->
                <div x-show="step === 1" x-transition>
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Identitas Pengunjung</h2>
                    <p class="text-xs text-slate-500 mb-6">Silakan lengkapi identitas Anda sebelum memilih layanan.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" x-model="formData.fullName" required
                                   class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                   placeholder="Contoh: Rahmat Hidayat">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor WhatsApp / HP <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" x-model="formData.phone" required
                                       class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="Contoh: 081234567890">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" x-model="formData.email" required
                                       class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="nama@email.com">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Asal Instansi / Perusahaan / Umum <span class="text-red-500">*</span></label>
                            <input type="text" name="institution" x-model="formData.institution" required
                                   class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                   placeholder="Contoh: Dinas Kesehatan Inhil / Universitas Riau / Umum">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tujuan Layanan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'pst' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="pst" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Pelayanan Statistik (PST)</span>
                                    <span class="text-xs text-slate-500 mt-1">Konsultasi data, perpustakaan, rekomendasi statistik.</span>
                                </label>

                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'non_pst' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="non_pst" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Layanan Non-PST</span>
                                    <span class="text-xs text-slate-500 mt-1">Audiensi dinas, kerjasama, surat menyurat, kedinasan.</span>
                                </label>

                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'pengaduan' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="pengaduan" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Pengaduan Layanan</span>
                                    <span class="text-xs text-slate-500 mt-1">Kritik, saran, atau aduan layanan BPS Inhil.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="button" @click="nextToStep2()"
                                class="px-6 py-3.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-sm transition flex items-center gap-2 shadow-md">
                            <span>Lanjut ke Rincian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- LANGKAH 2: RINCIAN LAYANAN (KONDISIONAL) -->
                <div x-show="step === 2" x-transition>
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Rincian Keperluan Layanan</h2>
                    <p class="text-xs text-slate-500 mb-6">Lengkapi kebutuhan spesifik kunjungan Anda.</p>

                    <!-- Kondisional PST -->
                    <template x-if="formData.purpose === 'pst'">
                        <div class="space-y-4">
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900">
                                <strong>Saluran Layanan:</strong> Tatap Muka Langsung di Kantor (Offline PST).
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Layanan Statistik <span class="text-red-500">*</span></label>
                                <select name="service_type" x-model="formData.serviceType" required
                                        class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition bg-white">
                                    <option value="">-- Pilih Jenis Layanan PST --</option>
                                    <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                                    <option value="Perpustakaan">Perpustakaan Statistik</option>
                                    <option value="Rekomendasi Kegiatan Statistik">Rekomendasi Kegiatan Statistik (ROMANTIK)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rincian Data / Keperluan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="4" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Tuliskan data statistik yang Anda cari (misal: Data PDRB Inhil 2020-2025, Angka Kemiskinan Kecamatan, dsb.)"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Dokumen Pendukung (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG, XLSX, DOCX (Maks. 5 MB). Contoh: Surat Tugas, Daftar Variabel.</p>
                            </div>
                        </div>
                    </template>

                    <!-- Kondisional Non-PST -->
                    <template x-if="formData.purpose === 'non_pst'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori Non-PST <span class="text-red-500">*</span></label>
                                <select name="non_pst_type" x-model="formData.nonPstType" required
                                        class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition bg-white">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Konsultasi">Konsultasi / Koordinasi</option>
                                    <option value="Kerjasama">Kerjasama / MoU</option>
                                    <option value="Lainnya">Kedinasan Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rincian Keperluan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="4" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Jelaskan secara ringkas maksud kunjungan dan pejabat/tim yang ingin ditemui..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Surat Tugas / Pengantar (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG, DOCX (Maks. 5 MB).</p>
                            </div>
                        </div>
                    </template>

                    <!-- Kondisional Pengaduan -->
                    <template x-if="formData.purpose === 'pengaduan'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Aduan / Topik <span class="text-red-500">*</span></label>
                                <input type="text" name="complaint_title" x-model="formData.complaintTitle" required
                                       class="w-full text-base px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="Contoh: Kendala akses data statistik / Pelayanan loket">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Isi Aduan / Masukan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="5" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Jelaskan secara rinci pengaduan atau masukan konstruktif Anda..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Bukti Dukung / Lampiran (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG (Maks. 5 MB).</p>
                            </div>
                        </div>
                    </template>

                    <div class="mt-8 flex justify-between items-center">
                        <button type="button" @click="step = 1"
                                class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition">
                            &larr; Kembali
                        </button>

                        <button type="button" @click="nextToStep3()"
                                class="px-6 py-3.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-sm transition flex items-center gap-2 shadow-md">
                            <span>Lanjut ke Ambil Foto</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- LANGKAH 3: AMBIL FOTO & KONFIRMASI (KIOS ONLY) -->
                <div x-show="step === 3" x-transition>
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Pengambilan Foto Tamu</h2>
                    <p class="text-xs text-slate-500 mb-6">Posisikan wajah Anda di depan kamera lobi untuk identifikasi tiket antrean.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <!-- Area Kamera -->
                        <div class="flex flex-col items-center">
                            <div class="relative w-full aspect-[4/3] bg-slate-900 rounded-2xl overflow-hidden shadow-inner border-2 border-slate-300 flex items-center justify-center">
                                <!-- Video Stream -->
                                <video x-ref="videoElement" autoplay playsinline muted
                                       class="w-full h-full object-cover"
                                       x-show="!photoTaken && cameraActive"></video>

                                <!-- Snapshot Preview -->
                                <img :src="photoData" alt="Foto Tamu"
                                     class="w-full h-full object-cover"
                                     x-show="photoTaken">

                                <!-- Camera Error / Initializing Overlay -->
                                <div x-show="!cameraActive && !photoTaken" class="text-center p-4 text-white">
                                    <svg class="w-10 h-10 text-slate-400 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <p class="text-xs text-slate-300">Menghubungkan ke kamera lobi...</p>
                                    <button type="button" @click="initCamera()" class="mt-3 px-3 py-1.5 rounded bg-blue-600 text-xs font-semibold">Coba Lagi</button>
                                </div>

                                <!-- Live Face Guide Overlay -->
                                <div x-show="cameraActive && !photoTaken" class="pointer-events-none absolute inset-0 border-2 border-dashed border-white/40 rounded-2xl m-6"></div>
                            </div>

                            <!-- Tombol Kontrol Kamera -->
                            <div class="mt-4 flex gap-3">
                                <button type="button" x-show="!photoTaken" @click="takePhoto()"
                                        :disabled="!cameraActive"
                                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 text-white font-bold text-sm transition flex items-center gap-2 shadow-md">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Ambil Foto</span>
                                </button>

                                <button type="button" x-show="photoTaken" @click="retakePhoto()"
                                        class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-semibold text-sm transition flex items-center gap-2 shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Ulangi Foto</span>
                                </button>
                            </div>
                        </div>

                        <!-- Ringkasan & Persetujuan UU PDP -->
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                <div class="font-bold text-slate-800 uppercase tracking-wider">Ringkasan Kunjungan:</div>
                                <div><span class="text-slate-500">Nama:</span> <strong x-text="formData.fullName"></strong></div>
                                <div><span class="text-slate-500">Instansi:</span> <strong x-text="formData.institution"></strong></div>
                                <div><span class="text-slate-500">Tujuan:</span> <strong class="uppercase" x-text="formData.purpose"></strong></div>
                            </div>

                            <!-- Checkbox Persetujuan UU PDP No. 27/2022 -->
                            <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="consent" value="1" x-model="formData.consent" required
                                           class="mt-1 w-5 h-5 rounded border-slate-300 text-blue-900 focus:ring-blue-800">
                                    <span class="text-xs text-slate-700 leading-relaxed">
                                        Saya menyatakan data yang saya isi adalah benar dan menyetujui pemrosesan data pribadi ini untuk keperluan pelayanan dan evaluasi statistik BPS Kab. Indragiri Hilir sesuai ketentuan <strong>UU Perlindungan Data Pribadi (UU No. 27/2022)</strong>.
                                    </span>
                                </label>
                            </div>

                            <!-- Keterangan Foto Wajib -->
                            <div class="text-xs text-slate-500">
                                <span class="text-red-500 font-bold">*</span> Tombol kirim hanya dapat ditekan setelah foto wajah diambil dan persetujuan dicentang.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Navigasi Akhir -->
                    <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-6">
                        <button type="button" @click="step = 2; stopCamera();"
                                class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition">
                            &larr; Kembali
                        </button>

                        <button type="submit"
                                :disabled="!photoTaken || !formData.consent"
                                class="px-8 py-4 rounded-xl bg-blue-900 hover:bg-blue-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-base transition flex items-center gap-2 shadow-xl shadow-blue-900/20">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Kirim & Cetak Tiket</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Hidden Canvas for Photo Capture & Compression (640px) -->
        <canvas x-ref="photoCanvas" class="hidden"></canvas>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900/90 text-center py-3 text-xs text-slate-400 border-t border-white/5">
        &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir &bull; Kios Resmi Terdaftar
    </footer>

    <script>
        // Jam digital header
        function updateLiveClock() {
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                const now = new Date();
                clockEl.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            }
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        function kioskForm() {
            return {
                step: 1,
                cameraActive: false,
                photoTaken: false,
                photoData: '',
                mediaStream: null,
                formData: {
                    fullName: '',
                    phone: '',
                    email: '',
                    institution: '',
                    purpose: 'pst',
                    serviceType: '',
                    nonPstType: '',
                    complaintTitle: '',
                    detail: '',
                    consent: false,
                },

                nextToStep2() {
                    if (!this.formData.fullName || !this.formData.phone || !this.formData.email || !this.formData.institution || !this.formData.purpose) {
                        alert('Harap lengkapi seluruh field wajib di langkah 1.');
                        return;
                    }
                    this.step = 2;
                },

                nextToStep3() {
                    if (this.formData.purpose === 'pst' && (!this.formData.serviceType || !this.formData.detail)) {
                        alert('Harap pilih Jenis Layanan PST dan isi Rincian Keperluan.');
                        return;
                    }
                    if (this.formData.purpose === 'non_pst' && (!this.formData.nonPstType || !this.formData.detail)) {
                        alert('Harap pilih Kategori Non-PST dan isi Rincian Keperluan.');
                        return;
                    }
                    if (this.formData.purpose === 'pengaduan' && (!this.formData.complaintTitle || !this.formData.detail)) {
                        alert('Harap isi Judul Aduan dan Isi Pengaduan.');
                        return;
                    }
                    this.step = 3;
                    this.$nextTick(() => {
                        this.initCamera();
                    });
                },

                async initCamera() {
                    try {
                        this.cameraActive = false;
                        const constraints = {
                            video: {
                                width: { ideal: 1280 },
                                height: { ideal: 720 },
                                facingMode: "user"
                            },
                            audio: false
                        };
                        this.mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.$refs.videoElement.srcObject = this.mediaStream;
                        this.cameraActive = true;
                    } catch (err) {
                        console.error('Kamera gagal diakses:', err);
                        this.cameraActive = false;
                        alert('Tidak dapat mengakses webcam lobi. Pastikan izin kamera aktif dan URL menggunakan HTTPS/localhost.');
                    }
                },

                takePhoto() {
                    if (!this.cameraActive) return;
                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.photoCanvas;
                    
                    // Batasi resolusi maksimal 640px sesuai PRD FR-04
                    const maxDimension = 640;
                    let width = video.videoWidth || 640;
                    let height = video.videoHeight || 480;

                    if (width > maxDimension) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, width, height);

                    // Kompresi JPEG kualitas 0.8
                    this.photoData = canvas.toDataURL('image/jpeg', 0.8);
                    this.photoTaken = true;
                    this.stopCamera();
                },

                retakePhoto() {
                    this.photoTaken = false;
                    this.photoData = '';
                    this.initCamera();
                },

                stopCamera() {
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(track => track.stop());
                        this.mediaStream = null;
                    }
                    this.cameraActive = false;
                }
            };
        }
    </script>
</body>
</html>
