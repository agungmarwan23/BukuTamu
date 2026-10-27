<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Layanan Online &bull; BPS Kabupaten Indragiri Hilir</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 text-slate-800 min-h-screen flex flex-col justify-between">
    <!-- Navbar Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-900 flex items-center justify-center font-bold text-white text-base shadow-sm">
                    BPS
                </div>
                <div>
                    <div class="font-extrabold text-blue-950 text-sm tracking-tight leading-tight">LAYANAN STATISTIK ONLINE</div>
                    <div class="text-[11px] font-semibold text-amber-600">BPS KABUPATEN INDRAGIRI HILIR</div>
                </div>
            </a>

            <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-600 hover:text-blue-900 transition flex items-center gap-1">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                <span>Masuk Petugas</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-3xl mx-auto px-4 py-8 flex-grow"
          x-data="onlineForm()"
          x-cloak>

        <!-- Form Heading -->
        <div class="text-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Formulir Permintaan Layanan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1.5 max-w-lg mx-auto">
                Silakan isi data permohonan Anda. Petugas PST BPS Inhil akan menindaklanjuti permintaan Anda melalui Email atau WhatsApp.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <div class="font-bold mb-1">Terdapat kesalahan isian:</div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200/80 overflow-hidden">
            <!-- Stepper Indicator -->
            <div class="bg-slate-50 border-b border-slate-200/60 px-6 py-4">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <div class="flex items-center gap-2" :class="step >= 1 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 1 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">1</span>
                        <span>Identitas</span>
                    </div>
                    <div class="h-0.5 flex-1 mx-3" :class="step >= 2 ? 'bg-blue-900' : 'bg-slate-200'"></div>

                    <div class="flex items-center gap-2" :class="step >= 2 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 2 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">2</span>
                        <span>Keperluan</span>
                    </div>
                    <div class="h-0.5 flex-1 mx-3" :class="step >= 3 ? 'bg-blue-900' : 'bg-slate-200'"></div>

                    <div class="flex items-center gap-2" :class="step >= 3 ? 'text-blue-900 font-bold' : 'text-slate-400'">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition"
                              :class="step >= 3 ? 'bg-blue-900 text-white' : 'bg-slate-200 text-slate-600'">3</span>
                        <span>Konfirmasi</span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('layanan.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8">
                @csrf
                <input type="hidden" name="channel" value="online">
                <!-- Honeypot Anti-Bot -->
                <input type="text" name="website_url" value="" class="hidden" tabindex="-1" autocomplete="off">

                <!-- LANGKAH 1: DATA PEMOHON -->
                <div x-show="step === 1" x-transition>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Identitas Pemohon</h2>
                    <p class="text-xs text-slate-500 mb-6">Pastikan kontak Anda aktif agar petugas dapat menghubungi Anda.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" x-model="formData.fullName" required
                                   class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                   placeholder="Masukkan nama lengkap Anda">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor WhatsApp / HP <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" x-model="formData.phone" required
                                       class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="Contoh: 081234567890">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" x-model="formData.email" required
                                       class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="nama@email.com">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Asal Instansi / Universitas / Umum <span class="text-red-500">*</span></label>
                            <input type="text" name="institution" x-model="formData.institution" required
                                   class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                   placeholder="Contoh: Dinas Kominfo / Mahasiswa UNRI / Umum">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tujuan Layanan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'pst' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="pst" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Pelayanan Statistik (PST)</span>
                                    <span class="text-xs text-slate-500 mt-1">Permintaan data, konsultasi statistik, atau rekomendasi kegiatan.</span>
                                </label>

                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'non_pst' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="non_pst" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Layanan Non-PST</span>
                                    <span class="text-xs text-slate-500 mt-1">Koordinasi kedinasan, kerjasama, surat permohonan.</span>
                                </label>

                                <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition"
                                       :class="formData.purpose === 'pengaduan' ? 'border-blue-900 bg-blue-50/60 ring-2 ring-blue-900' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input type="radio" name="purpose" value="pengaduan" x-model="formData.purpose" class="sr-only">
                                    <span class="font-bold text-slate-900 text-sm">Pengaduan</span>
                                    <span class="text-xs text-slate-500 mt-1">Saran, kritik, atau pengaduan terhadap layanan.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="button" @click="nextToStep2()"
                                class="px-6 py-3 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-sm transition flex items-center gap-2 shadow-md">
                            <span>Lanjut ke Rincian</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- LANGKAH 2: DETAIL KEPERLUAN -->
                <div x-show="step === 2" x-transition>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Rincian Permohonan</h2>
                    <p class="text-xs text-slate-500 mb-6">Tuliskan spesifikasi kebutuhan data atau permohonan Anda.</p>

                    <!-- PST Online -->
                    <template x-if="formData.purpose === 'pst'">
                        <div class="space-y-4">
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900">
                                <strong>Saluran Layanan:</strong> Online / Daring (Ditindaklanjuti melalui WA / Email).
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Layanan Statistik <span class="text-red-500">*</span></label>
                                <select name="service_type" x-model="formData.serviceType" required
                                        class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition bg-white">
                                    <option value="">-- Pilih Jenis Layanan PST --</option>
                                    <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                                    <option value="Perpustakaan">Perpustakaan Statistik</option>
                                    <option value="Rekomendasi Kegiatan Statistik">Rekomendasi Kegiatan Statistik (ROMANTIK)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rincian Data yang Diperlukan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="4" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Sebutkan variabel data, tahun rilis, dan level wilayah yang Anda butuhkan..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Dokumen Pendukung (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG, XLSX, DOCX (Maks. 5 MB).</p>
                            </div>
                        </div>
                    </template>

                    <!-- Non-PST Online -->
                    <template x-if="formData.purpose === 'non_pst'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori Permohonan <span class="text-red-500">*</span></label>
                                <select name="non_pst_type" x-model="formData.nonPstType" required
                                        class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition bg-white">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Konsultasi">Konsultasi / Koordinasi</option>
                                    <option value="Kerjasama">Kerjasama / MoU</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rincian Keperluan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="4" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Tuliskan tujuan dan penjelasan permohonan..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Surat Pengantar / Berkas (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG, DOCX (Maks. 5 MB).</p>
                            </div>
                        </div>
                    </template>

                    <!-- Pengaduan Online -->
                    <template x-if="formData.purpose === 'pengaduan'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Aduan <span class="text-red-500">*</span></label>
                                <input type="text" name="complaint_title" x-model="formData.complaintTitle" required
                                       class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                       placeholder="Judul pengaduan Anda">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Isi Pengaduan <span class="text-red-500">*</span></label>
                                <textarea name="detail" x-model="formData.detail" rows="5" required
                                          class="w-full text-sm px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                                          placeholder="Uraikan isi pengaduan, kritik, atau saran secara lengkap..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Upload Bukti Dukung (Opsional)</label>
                                <input type="file" name="attachment"
                                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5">
                                <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG (Maks. 5 MB).</p>
                            </div>
                        </div>
                    </template>

                    <div class="mt-8 flex justify-between items-center">
                        <button type="button" @click="step = 1"
                                class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition">
                            &larr; Kembali
                        </button>

                        <button type="button" @click="nextToStep3()"
                                class="px-6 py-3 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm transition flex items-center gap-2 shadow-md">
                            <span>Lanjut ke Konfirmasi</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- LANGKAH 3: PERSETUJUAN & KIRIM -->
                <div x-show="step === 3" x-transition>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Persetujuan & Pengiriman</h2>
                    <p class="text-xs text-slate-500 mb-6">Tinjau ringkasan data dan setujui pernyataan data pribadi sebelum mengirim.</p>

                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                            <div class="font-bold text-slate-800 uppercase tracking-wider">Ringkasan Permohonan:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-slate-200">
                                <div><span class="text-slate-500">Nama:</span> <strong x-text="formData.fullName"></strong></div>
                                <div><span class="text-slate-500">Instansi:</span> <strong x-text="formData.institution"></strong></div>
                                <div><span class="text-slate-500">WhatsApp/HP:</span> <strong x-text="formData.phone"></strong></div>
                                <div><span class="text-slate-500">Email:</span> <strong x-text="formData.email"></strong></div>
                                <div><span class="text-slate-500">Tujuan:</span> <strong class="uppercase" x-text="formData.purpose"></strong></div>
                                <div><span class="text-slate-500">Saluran:</span> <strong>Layanan Online</strong></div>
                            </div>
                        </div>

                        <!-- Checkbox UU PDP -->
                        <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="consent" value="1" x-model="formData.consent" required
                                       class="mt-1 w-5 h-5 rounded border-slate-300 text-blue-900 focus:ring-blue-800">
                                <span class="text-xs text-slate-700 leading-relaxed">
                                    Saya menyatakan data yang diisikan adalah benar dan menyetujui pemrosesan data pribadi untuk keperluan pelayanan serta evaluasi kepuasan layanan BPS Kab. Indragiri Hilir sesuai ketentuan <strong>UU Perlindungan Data Pribadi (UU No. 27/2022)</strong>.
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-6">
                        <button type="button" @click="step = 2"
                                class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition">
                            &larr; Kembali
                        </button>

                        <button type="submit"
                                :disabled="!formData.consent"
                                class="px-8 py-3.5 rounded-xl bg-blue-900 hover:bg-blue-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm transition flex items-center gap-2 shadow-lg shadow-blue-900/20">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Kirim Permohonan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 text-center py-4 text-xs text-slate-500">
        &copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Indragiri Hilir &bull; Pelayanan Statistik Terpadu
    </footer>

    <script>
        function onlineForm() {
            return {
                step: 1,
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
                }
            };
        }
    </script>
</body>
</html>
