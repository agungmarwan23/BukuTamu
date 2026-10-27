# PROMPT PLAYBOOK — Antigravity

Satu fase = satu percakapan/sesi baru. Salin prompt dari blok kode ke Antigravity.

## Cara kerja (ulangi setiap fase)

1. Buka folder `D:\Ngoding\BukuTamu` sebagai workspace di Antigravity.
2. Mulai percakapan baru, tempel prompt fase.
3. Minta rencana dulu. Baca rencana, koreksi, baru setujui.
4. Setelah selesai: jalankan aplikasi, uji manual sesuai "Cek hasil", lalu `git commit`.
5. Pastikan agen sudah memperbarui `PROJECT_LOG.md`. Jika gagal, pakai prompt "Saat ada yang gagal".

## Struktur folder

```
D:\Ngoding\BukuTamu\
├── AGENTS.md
├── PROJECT_LOG.md
└── docs\
    ├── PRD.md
    └── PROMPT_PLAYBOOK.md
```
Laravel di-install **ke dalam folder ini** (Fase 1), jadi file di atas tetap di root proyek.

---

## Fase 0 — Persiapan (kerjakan manual, tanpa Antigravity)

- Pasang Laragon dengan PHP 8.3 (sama dengan cPanel), MySQL, Composer, Node.js LTS, Git.
- Salin 4 file di atas ke folder proyek, jalankan `git init`.
- Versi PHP cPanel kantor sudah diketahui (8.3). Pantau kabar upgrade dari tim IT pusat; jika naik, uji ulang aplikasi di versi baru.
- Siapkan PC kios: webcam berfungsi, printer DT 58D terpasang lewat USB dengan driver Windows dan lolos test print (penjual menyebut Windows lewat USB, Bluetooth untuk HP). Catat hasilnya di `PROJECT_LOG.md`.

---

## Fase 1 — Fondasi

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 2, 6, 8).

Tujuan: membuat fondasi proyek Laravel + MySQL di folder ini.

Tugas:
1. Install Laravel (versi stabil terbaru yang mendukung PHP 8.3) langsung di root folder ini tanpa menimpa AGENTS.md, PROJECT_LOG.md, dan docs/. Kunci config.platform.php = 8.3 di composer.json.
2. Konfigurasi .env.example (database MySQL, timezone Asia/Jakarta, locale id, APP_NAME).
3. Auth session-based (Laravel Breeze, Blade) dengan login memakai username, bukan email.
4. Peran admin, resepsionis, petugas + middleware peran + redirect setelah login sesuai peran.
5. Semua migration sesuai PRD bagian 6 (users, visits, visit_attachments, visit_status_logs, call_events, duty_schedules, duty_swaps, notification_logs, settings, kiosk_devices), dengan foreign key dan index.
6. Seeder: 1 admin, 1 resepsionis, 3 petugas, setting default (skm_url kosong).
7. Layout dasar Blade + Tailwind, bahasa Indonesia.

Batasan: ikuti AGENTS.md (kompatibel cPanel). Jangan membuat fitur form tamu dulu.
Berikan rencana implementasi terlebih dahulu dan tunggu persetujuan saya.
Di akhir: perbarui PROJECT_LOG.md.
```
**Cek hasil:** `php artisan migrate:fresh --seed` berhasil, tiga peran bisa login dan masuk halaman berbeda.

---

## Fase 2 — Form Tamu (kios dan online)

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 4, 5.1, 7).

Tujuan: dua pintu masuk form dengan komponen bersama (FR-01 sampai FR-09).

Tugas:
1. Komponen multi-step form yang dipakai bersama:
   - Langkah 1: Nama Lengkap, Email, No. HP (normalisasi ke 62...), Asal Instansi, Tujuan Layanan (PST / Non-PST / Pengaduan).
   - Langkah 2 (kondisional dengan Alpine.js, divalidasi ulang di server):
     PST: Jenis Layanan (Konsultasi Statistik, Perpustakaan, Rekomendasi Kegiatan Statistik), Rincian Keperluan, upload dokumen pendukung opsional. Saluran Online/Offline ditentukan otomatis dari pintu masuk, bukan dipilih tamu.
     Non-PST: dropdown (Konsultasi, Kerjasama, Lainnya) + Rincian Keperluan.
     Pengaduan: Judul Aduan + Isi Pengaduan.
2. Mode kios "/kios" (tamu di kantor): tambahkan langkah 3 berisi ambil foto dengan getUserMedia, preview, tombol ulangi, kompres JPEG maksimal 640 px, checkbox persetujuan data pribadi. Tombol kirim terkunci sampai foto ada. Halaman hanya bisa dibuka dari perangkat terdaftar: tabel kiosk_devices (token di-hash) + cookie perangkat; perangkat belum terdaftar mendapat 403. Untuk sekarang buat artisan command untuk membuat token aktivasi (UI admin menyusul di Fase 5).
3. Form online "/layanan" (dan "/" mengarah ke sini): tanpa foto dan tanpa nomor antrean, dengan checkbox persetujuan. Setelah kirim, tampilkan kode tiket unik (mis. ONL-0001) dan info bahwa petugas akan menghubungi lewat email/WA.
4. Simpan foto dan lampiran di storage PRIVAT (bukan public). Validasi tipe (PDF/JPG/PNG/XLSX/DOCX) dan ukuran maksimal 5 MB.
5. Rate limiting dan honeypot anti-bot, terutama di route online yang publik.
6. Layar sukses kios: tampilkan nomor tiket sementara (nomor antrean sungguhan dibuat di Fase 3), tombol Cetak, auto-reset 30 detik.
7. Desain responsif; kios untuk PC/tablet, form online nyaman di HP.

Batasan: kamera hanya jalan di HTTPS/localhost; untuk uji lewat LAN catat kendalanya di PROJECT_LOG.md, jangan dihack.
Berikan rencana dulu dan tunggu persetujuan. Di akhir: tulis feature test untuk validasi kondisional, foto wajib di kios, foto tidak diminta di online, dan 403 untuk perangkat tidak terdaftar, lalu perbarui PROJECT_LOG.md.
```
**Cek hasil:** tiap jalur (PST/Non-PST/Pengaduan) tampil benar di kedua pintu; di /kios tanpa foto tidak bisa kirim; /kios dari perangkat tak terdaftar ditolak; file foto tidak bisa dibuka lewat URL langsung.

---

## Fase 3 — Antrean, Layar TV, Suara, Struk

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 5.2, 7).

Tujuan: sistem antrean untuk tamu kios (FR-10 sampai FR-15).

Tugas:
1. Nomor antrean harian berformat PST-01, PST-02, ... (menjadi PST-100 setelah 99), reset otomatis per tanggal, aman dari duplikat saat input bersamaan (transaksi DB + unique constraint). Tulis test konkurensi sederhana. Tiket online tidak diberi nomor antrean.
2. Halaman resepsionis "/resepsionis": daftar antrean hari ini (hanya tiket kios) dengan tombol Panggil, Ulang, Lewati. Setiap panggilan masuk ke call_events.
3. Halaman layar TV "/layar" (publik, read-only): nomor sedang dipanggil, 5 panggilan terakhir, jumlah menunggu. Jangan tampilkan nama tamu. Polling tiap 3 detik.
4. Suara: Web Speech API bahasa id-ID, ucapkan nomor dengan jelas (mis. "P S T nol satu"). Tambahkan tombol "Aktifkan Suara" karena browser butuh interaksi pengguna. Suara hanya terpicu untuk call_events yang baru.
5. Struk: halaman cetak lebar 58 mm (CSS @page, area cetak efektif sekitar 48 mm) berisi nomor, tanggal/jam, jenis layanan, nama kantor. Cetak lewat tombol "Cetak Nomor Antrean" di layar sukses (bisa diulang). Printer DT 58D terpasang via USB di PC kios. Dokumentasikan opsi Chrome --kiosk-printing.
6. Pasang nomor antrean sungguhan ke layar sukses kios dari Fase 2.

Berikan rencana dulu dan tunggu persetujuan. Di akhir: perbarui PROJECT_LOG.md.
```
**Cek hasil:** buka "/layar" di TV, tekan Panggil di resepsionis, nomor tampil dan suara terdengar; tombol Cetak mengeluarkan struk di DT 58D dengan teks tidak terpotong.

---

## Fase 4 — Portal Petugas

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 5.3, 6).

Tujuan: portal petugas "/petugas" (FR-20 sampai FR-24).

Tugas:
1. Penugasan tiket: default ke petugas piket hari itu dengan tiket aktif paling sedikit; resepsionis bisa mengubah.
2. Halaman "Antrean saya": nomor, nama tamu, instansi, keperluan, lampiran, foto, tombol Mulai Layani dan Selesai, kolom catatan layanan.
3. Alur status Menunggu -> Dipanggil -> Dilayani -> Selesai (+ Tidak Hadir, Dibatalkan). Validasi transisi dan catat di visit_status_logs.
4. Swap petugas: petugas atau admin memilih tanggal, petugas pengganti, dan alasan. Simpan ke duty_swaps TANPA mengubah duty_schedules.
5. Halaman "Jadwal saya" yang memperlihatkan jadwal asli dan swap yang berlaku.
6. Tab "Permintaan Online": daftar tiket channel online (kode ONL-xxxx), petugas menandai status dan mengisi catatan tindak lanjut.

Berikan rencana dulu dan tunggu persetujuan. Di akhir: test untuk transisi status dan swap (pastikan jadwal asli tidak berubah), lalu perbarui PROJECT_LOG.md.
```

---

## Fase 5 — Admin

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 5.4).

Tujuan: panel admin "/admin" (FR-30 sampai FR-35).

Tugas:
1. Daftar kunjungan dengan filter (tanggal, layanan, status, petugas), pencarian, halaman detail (foto dan lampiran lewat route terautentikasi).
2. CRUD jadwal piket (tampilan kalender atau tabel) dengan penugasan massal satu bulan.
3. Dasbor: kunjungan hari ini/minggu/bulan, per jenis layanan, per instansi, rata-rata waktu tunggu dan waktu layanan, tiket belum selesai. Gunakan Chart.js dari file lokal (bukan CDN).
4. Analitik swap: siapa paling sering digantikan dan paling sering menggantikan per periode, dari tabel duty_swaps.
5. Ekspor Excel/CSV mengikuti filter yang aktif.
6. Kelola akun: tambah, nonaktifkan, reset password.
7. Kelola perangkat kios: buat token aktivasi, daftar perangkat, nonaktifkan.

Berikan rencana dulu dan tunggu persetujuan. Di akhir: perbarui PROJECT_LOG.md.
```

---

## Fase 6 — Notifikasi

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 5.5).

Tujuan: notifikasi (FR-40 sampai FR-42) dengan pola driver.

Tugas:
1. NotificationService dengan driver log, mail, dan whatsapp (kerangka HTTP client yang URL, token, dan nomor pengirimnya dari .env; penyedia konkret belum dipilih).
2. Command + scheduler: kirim pengingat piket tiap hari kerja pukul 07:00 ke petugas yang bertugas (memperhitungkan swap).
3. Saat tiket Selesai: kirim pesan berisi tautan SKM (dari settings.skm_url) ke tamu. Lewati jika skm_url kosong dan catat peringatan.
4. Semua kiriman dicatat di notification_logs (berhasil/gagal) dan gagal tidak boleh menjatuhkan proses utama. Gunakan job sinkron/database queue, tanpa Redis.
5. Dokumentasikan cron cPanel dan Task Scheduler Windows untuk "php artisan schedule:run".

Berikan rencana dulu dan tunggu persetujuan. Di akhir: perbarui PROJECT_LOG.md.
```

---

## Fase 7 — Hardening & Dokumentasi

```
Baca PROJECT_LOG.md, AGENTS.md, dan docs/PRD.md (bagian 7, 10).

Tujuan: kesiapan produksi.

Tugas:
1. Audit keamanan: otorisasi per route dan per peran, akses file privat, CSRF, rate limit, validasi upload, header keamanan dasar.
2. Jalankan seluruh test, perbaiki yang gagal, tambah test untuk kriteria penerimaan PRD bagian 10.
3. Tulis README.md: instalasi lokal, konfigurasi HTTPS lokal untuk kamera di LAN (mkcert/Laragon SSL, cara mempercayai sertifikat di tablet), mode kios Chrome untuk PC kios dan TV, setting printer DT 58D via USB (driver Windows, kertas 58 mm, test print), dan cara mendaftarkan perangkat kios.
4. Backup database terjadwal (command artisan + cara menjalankannya).
5. Command artisan terjadwal yang menghapus file foto lebih dari 365 hari (data teks tetap), dengan opsi --dry-run dan log jumlah file terhapus.
6. Laporkan setiap kriteria penerimaan: lulus/gagal/belum teruji.

Berikan rencana dulu dan tunggu persetujuan. Di akhir: perbarui PROJECT_LOG.md.
```

---

## Fase 8 — Deploy cPanel

```
Baca PROJECT_LOG.md, AGENTS.md, dan README.md.

Tujuan: panduan dan paket deploy ke cPanel.

Tugas:
1. Buat checklist deploy: versi PHP (target 8.3, plus cara menguji ulang jika cPanel di-upgrade) dan ekstensi yang dibutuhkan, struktur folder (isi public/ ke public_html, aplikasi di luar public_html), .env produksi, composer install --no-dev, php artisan migrate --force, storage:link, cache config/route/view, izin folder storage dan bootstrap/cache.
2. Pastikan aset hasil build sudah ada di public/build.
3. Panduan cron cPanel untuk scheduler dan AutoSSL untuk HTTPS.
4. Daftar risiko dan langkah rollback.

Jangan menjalankan perintah yang menyentuh server produksi; hanya hasilkan panduan dan skrip. Perbarui PROJECT_LOG.md.
```

---

## Prompt bantu

**Saat ada yang gagal**
```
Fase [X] gagal pada: [jelaskan + tempel pesan error].
Jangan langsung mengubah kode. Pertama, diagnosis penyebab dengan bukti (log, langkah reproduksi).
Kedua, usulkan 1-2 solusi dan dampaknya. Setelah saya setuju, perbaiki.
Lalu catat kegagalan dan solusinya di bagian 6 PROJECT_LOG.md.
```

**Penutup sesi**
```
Perbarui PROJECT_LOG.md: ringkas yang dikerjakan, hasil (berhasil/gagal/sebagian), file penting, masalah dan solusi, keputusan baru, tabel progres fase, serta langkah berikutnya. Jangan hapus entri lama.
```

**Mengubah kebutuhan**
```
Ada perubahan kebutuhan: [jelaskan]. Cari bagian PRD yang terdampak (sebutkan ID FR), usulkan perubahan PRD dan kode yang diperlukan, tunggu persetujuan saya, lalu perbarui docs/PRD.md dan PROJECT_LOG.md.
```

**Ganti model/tool** — pakai prompt handoff di `PROJECT_LOG.md` bagian 8.
