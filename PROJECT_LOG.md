# PROJECT_LOG — Buku Tamu & Antrean BPS Kab. Indragiri Hilir

> File ini adalah **memori proyek**. Baca dari atas sebelum bekerja, perbarui setelah selesai.
> Jika berganti model/tool, cukup minta model baru membaca file ini + `AGENTS.md` + `docs/PRD.md`.

## 1. Apa yang dibuat
Aplikasi web buku tamu digital dan antrean layanan untuk BPS Kab. Indragiri Hilir: tamu mengisi form di tablet lobi (foto wajib), mendapat nomor antrean; resepsionis memanggil nomor (layar TV + suara + struk); petugas piket melayani dan menandai status; admin memantau dasbor, jadwal piket, dan ekspor laporan. Detail lengkap: `docs/PRD.md`.

- Folder kerja: `D:\Ngoding\BukuTamu`
- Tool pengembangan: Google Antigravity
- Stack: Laravel + MySQL, Blade + Tailwind + Alpine.js

## 2. Keputusan yang sudah dikunci

| Topik | Keputusan | Tanggal |
|---|---|---|
| Stack | Laravel + MySQL (aman untuk cPanel dan LAN) | 2026-10-27 |
| Hosting | Tahap awal: PC sendiri, akses lewat LAN. Berikutnya: cPanel di jaringan kantor | 2026-10-27 |
| Perangkat lobi | PC kios (webcam + printer), TV/monitor + speaker; tablet opsional | 2026-10-27 |
| Nomor antrean | Reset otomatis tiap hari, format PST-01, PST-02, … | 2026-10-27 |
| Pemanggil antrean | Resepsionis/admin untuk semua petugas | 2026-10-27 |
| Login | Username + password, akun dibuat admin | 2026-10-27 |
| SKM | Memakai tautan SKM resmi yang sudah ada (disimpan di pengaturan) | 2026-10-27 |
| WA gateway | Belum dipilih; fase awal pakai driver `log`/email, penyedia dipilih nanti | 2026-10-27 |
| Realtime | Polling (tanpa WebSocket) demi kompatibilitas cPanel | 2026-10-27 |
| PHP | cPanel kantor PHP 8.3, kemungkinan di-upgrade; PHP 8.2 ke bawah tidak didukung | 2026-10-27 |
| Tamu online | Form dari luar kantor tanpa foto, upload file opsional; foto hanya untuk tamu di kantor | 2026-10-27 |
| Retensi foto | 1 tahun (file dihapus otomatis, data teks tetap) | 2026-10-27 |
| Printer | DT 58D (58 mm), terpasang di PC kios, dicetak lewat tombol Cetak | 2026-10-27 |

## 3. Asumsi & pertanyaan terbuka
Lihat `docs/PRD.md` bagian 11 (A1–A11). Saat satu terjawab, pindahkan ke tabel keputusan di atas.

## 4. Progres per fase

Status: ⬜ belum · 🟨 berjalan · ✅ selesai · ❌ gagal/diblokir

| Fase | Isi | Status | Catatan |
|---|---|---|---|
| 0 | Persiapan environment (Laragon PHP 8.3, Git, webcam + printer di PC kios) | ✅ | PHP 8.3.36, Composer 2.10.3, MySQL 8.4 terverifikasi |
| 1 | Fondasi: Laravel, auth, peran, migrasi, seeder | ✅ | Laravel, platform PHP 8.3 dikunci, auth username, 3 peran, semua migrasi PRD, seeder, test 100% lulus |
| 2 | Form tamu (wizard, kondisional, foto, upload) | ✅ | Kios terdaftar (/kios), form online (/layanan), foto privat, anti-bot, 44 tests passed |
| 3 | Antrean: nomor, panggil, layar TV, TTS, struk | ⬜ | |
| 4 | Portal petugas: antrean saya, status, swap | ⬜ | |
| 5 | Admin: daftar kunjungan, jadwal piket, dasbor, ekspor | ⬜ | |
| 6 | Notifikasi: pengingat petugas, tautan SKM | ⬜ | |
| 7 | Hardening, tes, HTTPS LAN, dokumentasi | ⬜ | |
| 8 | Deploy cPanel | ⬜ | |

## 5. Log kerja (terbaru di atas)

### Template entri
```
### YYYY-MM-DD — Fase X — [model/tool]
**Dikerjakan:** …
**Hasil:** ✅ berhasil / ❌ gagal / 🟨 sebagian — …
**File penting:** …
**Masalah & solusi:** …
**Keputusan baru:** …
**Langkah berikutnya:** …
```

### 2026-10-27 — Fase 2 — Antigravity (Gemini 3.8 Flash)
**Dikerjakan:**
- Membuat komponen form multi-step (Langkah 1: Identitas, Langkah 2: Rincian Keperluan kondisional PST/Non-PST/Pengaduan, Langkah 3: Foto & Konfirmasi).
- Mengimplementasikan mode Kios `/kios`:
  - Proteksi perangkat terdaftar dengan `KioskDeviceMiddleware` dan cookie HTTP-only `kiosk_device_token` (perangkat belum terdaftar menerima 403 Forbidden).
  - Membuat artisan command `php artisan kiosk:register "{nama_perangkat}"` dan controller aktivasi `KioskDeviceController@activate`.
  - Integrasi webcam dengan `navigator.mediaDevices.getUserMedia`, live preview, snapshot, tombol ulangi foto, dan kompresi JPEG via HTML5 Canvas (maks. 640px).
  - Checkbox persetujuan data pribadi sesuai UU No. 27/2022 (UU PDP). Tombol kirim terkunci sebelum ada foto dan centang persetujuan.
  - Layar sukses kios menampilkan kode tiket sementara (`KIO-YYYYMMDD-XXXX`), tombol Cetak (CSS @page untuk printer struk thermal 58 mm), dan hitung mundur auto-reset 30 detik.
- Mengimplementasikan form online publik `/layanan` (dan `/` dialihkan ke `/layanan`):
  - Tanpa pengambilan foto dan tanpa nomor antrean lobi.
  - Tampilan sukses menampilkan kode tiket (`ONL-YYYYMMDD-XXXX`) dan informasi tindak lanjut petugas via Email/WhatsApp.
- Menyimpan foto dan berkas lampiran di storage privat (`storage/app/private/photos` dan `storage/app/private/attachments`), divalidasi tipe (PDF, JPG, PNG, XLSX, DOCX) dan ukuran maks 5 MB.
- Normalisasi nomor HP ke format internasional `62...` melalui `PhoneNormalizer`.
- Menambahkan honeypot field anti-bot (`website_url`) dan rate limiting (`throttle:10,1` dan `throttle:15,1`).
- Membuat pengujian feature test di `tests/Feature/GuestVisitTest.php` (12 test) dan menjalankan seluruh suite test (44 test lulus 100%).

**Hasil:** ✅ Berhasil. Form Kios dan Online siap digunakan, teruji validasi kondisional, foto privat, proteksi perangkat kios, serta anti-bot.
**File penting:**
- `app/Http/Controllers/GuestVisitController.php`
- `app/Http/Controllers/KioskDeviceController.php`
- `app/Http/Middleware/KioskDeviceMiddleware.php`
- `app/Http/Requests/StoreGuestVisitRequest.php`
- `app/Console/Commands/RegisterKioskDevice.php`
- `app/Services/PhoneNormalizer.php`
- `resources/views/kiosk/form.blade.php`, `resources/views/kiosk/success.blade.php`
- `resources/views/online/form.blade.php`, `resources/views/online/success.blade.php`
- `resources/views/errors/403.blade.php`
- `routes/web.php`, `bootstrap/app.php`
- `tests/Feature/GuestVisitTest.php`
**Masalah & solusi:**
- Akses kamera browser mewajibkan HTTPS atau localhost: diuji di localhost, untuk akses via LAN didokumentasikan butuh sertifikat SSL lokal (Fase 7).
**Keputusan baru:**
- Rute `/` dialihkan ke `/layanan` sesuai PRD FR-09. Header form online menyediakan tautan ke login petugas.
**Langkah berikutnya:** Fase 3 — Antrean, Layar TV, Suara (TTS), dan Struk Thermal 58 mm.

### 2026-10-27 — Fase 1 — Antigravity (Gemini 3.8 Flash)
**Dikerjakan:**
- Menginstal Laravel versi stabil terbaru di root folder tanpa menimpa dokumen proyek yang sudah ada.
- Mengunci `config.platform.php = "8.3"` di `composer.json` agar seluruh paket selalu kompatibel dengan PHP 8.3 cPanel.
- Mengonfigurasi `.env` dan `.env.example`: database MySQL `buku_tamu`, timezone `Asia/Jakarta`, locale `id`, `APP_NAME="Buku Tamu BPS Inhil"`.
- Mengimplementasikan auth session-based (Laravel Breeze Blade + Alpine + Tailwind) dengan autentikasi menggunakan `username` (bukan email) dan verifikasi status aktif (`is_active`).
- Membuat `RoleMiddleware` dan routing role (`admin`, `resepsionis`, `petugas`) beserta auto-redirect login sesuai peran.
- Membuat seluruh migrasi sesuai PRD bagian 6: `users`, `kiosk_devices`, `settings`, `visits`, `visit_attachments`, `visit_status_logs`, `call_events`, `duty_schedules`, `duty_swaps`, `notification_logs` dengan foreign key dan index.
- Membuat seluruh Eloquent Model (`User`, `Visit`, `VisitAttachment`, `VisitStatusLog`, `CallEvent`, `DutySchedule`, `DutySwap`, `NotificationLog`, `Setting`, `KioskDevice`).
- Membuat `DatabaseSeeder`: 1 admin (`admin`), 1 resepsionis (`resepsionis`), 3 petugas (`petugas1`, `petugas2`, `petugas3`), dan setting default BPS Kab. Indragiri Hilir (`skm_url` kosong).
- Menyiapkan layout Blade + Tailwind bahasa Indonesia dengan identitas visual modern BPS (Navy + Amber).
- Menguji seluruh fitur lewat unit/feature test (`AuthenticationTest`, `RegistrationTest`), seluruh 32 test lulus 100%.
- Menjalankan `npm run build` dan memastikan aset di `public/build` di-track git untuk cPanel.

**Hasil:** ✅ Berhasil. `php artisan migrate:fresh --seed` sukses, seluruh 3 peran teruji dan terautentikasi sesuai alur masing-masing.
**File penting:**
- `composer.json`, `.env`, `.env.example`, `config/app.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_10_27_000001_create_buku_tamu_core_tables.php`
- `app/Models/*` (10 model)
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `routes/web.php`, `routes/auth.php`
- `resources/views/layouts/*`, `resources/views/auth/login.blade.php`, `resources/views/welcome.blade.php`, `resources/views/{admin,resepsionis,petugas}/dashboard.blade.php`
- `tests/Feature/Auth/AuthenticationTest.php`, `tests/Feature/Auth/RegistrationTest.php`
**Masalah & solusi:**
- Laragon CLI binaries belum ada di global system PATH default: menggunakan path lengkap `C:\laragon\bin\...` di PowerShell.
- Default Breeze mengabaikan `public/build` di `.gitignore`: aturan dihapus agar aset ikut ter-commit untuk hosting cPanel.
**Keputusan baru:**
- Registrasi publik dinonaktifkan (`/register` 404) karena akun pengguna hanya dibuat oleh Admin sesuai PRD.
**Langkah berikutnya:** Fase 2 — Form Tamu (wizard kios dengan foto & form online tanpa foto).

### 2026-10-27 — Perencanaan (revisi) — Claude (chat)
**Dikerjakan:** Menjawab pertanyaan terbuka A1, A2, A5, A7, A8 dan memperbarui PRD (v2.1), AGENTS.md, playbook, serta log ini. Menambah mode kios `/kios` (perangkat terdaftar) vs form online `/layanan`, tabel `kiosk_devices`, kode tiket online, retensi foto 1 tahun, format nomor `PST-01`, printer 58 mm.
**Hasil:** ✅ Dokumen diperbarui. Belum ada kode.
**Catatan teknis penting:** deskripsi penjual printer DT 58D menyebut Bluetooth untuk Android/iOS dan USB untuk Windows, jadi sambungkan lewat USB ke PC kios dan uji test print di Fase 0. Browser kios butuh webcam dan HTTPS/localhost.
**Pertanyaan terbuka:** A3, A4, A6, A9, A10, A11 (lihat PRD bagian 11).
**Langkah berikutnya:** Fase 0, lalu Fase 1.

### 2026-10-27 — Perencanaan — Claude (chat)
**Dikerjakan:** Wawancara kebutuhan (3 putaran), upgrade PRD awal menjadi PRD v2, membuat `AGENTS.md`, `PROJECT_LOG.md`, dan `docs/PROMPT_PLAYBOOK.md`.
**Hasil:** ✅ Dokumen perencanaan selesai. Belum ada kode.
**Keputusan baru:** lihat tabel keputusan di atas.
**Catatan teknis penting:** kamera browser hanya jalan di HTTPS/localhost → rencanakan sertifikat lokal untuk LAN (Fase 7). Audio TTS butuh satu klik "Aktifkan Suara" pada halaman layar.
**Langkah berikutnya:** Fase 0 — siapkan environment, salin dokumen ini ke `D:\Ngoding\BukuTamu`, (versi PHP cPanel sudah diketahui: 8.3).

## 6. Kegagalan & pelajaran
Catat setiap hal yang gagal agar tidak diulang.

| Tanggal | Apa yang gagal | Penyebab | Solusi/keputusan |
|---|---|---|---|
| — | — | — | — |

## 7. Hasil diskusi penting
Catat ringkasan diskusi yang mengubah arah (bukan seluruh percakapan).

- 2026-10-27: Stack dipilih Laravel karena rencana deploy ke cPanel; Next.js/Node berisiko tidak didukung hosting.
- 2026-10-27: Pemanggilan antrean dipusatkan di resepsionis agar suara dan layar tidak bentrok antarpetugas.
- 2026-10-27: Tamu di kantor dan tamu online dipisah lewat dua pintu masuk (`/kios` terdaftar vs `/layanan` publik) karena foto hanya wajib di kantor.
- 2026-10-27: PHP cPanel 8.3 dan ada arahan upgrade dari tim IT pusat, jadi kode harus kompatibel dengan versi PHP yang lebih baru.

## 8. Prompt handoff untuk model baru
Salin ke awal percakapan:

```
Kita melanjutkan proyek "Buku Tamu BPS Kab. Indragiri Hilir" di D:\Ngoding\BukuTamu.
Sebelum melakukan apa pun, baca berurutan: PROJECT_LOG.md, AGENTS.md, docs/PRD.md.
Lalu ringkas dalam 5 poin: tujuan proyek, keputusan yang sudah dikunci, fase yang sedang berjalan,
kegagalan sebelumnya, dan langkah berikutnya. Tunggu konfirmasi saya sebelum menulis kode.
```
