# PRD — Sistem Buku Tamu & Antrean Layanan BPS Kabupaten Indragiri Hilir

> Versi 2.1 (draft) · 27 Oktober 2026 · Sumber: PRD awal + hasil wawancara (4 putaran).
> Dokumen ini hidup. Setiap perubahan keputusan dicatat juga di `PROJECT_LOG.md`.

## 1. Ringkasan

Aplikasi web untuk **registrasi tamu (buku tamu digital)**, **manajemen antrean**, dan **penugasan petugas piket** di BPS Kabupaten Indragiri Hilir. Tamu mengisi form di tablet lobi, mengambil foto, lalu mendapat nomor antrean. Resepsionis memanggil nomor, petugas piket melayani dan menandai status, admin memantau seluruh data dan jadwal.

**Tujuan:** (1) data kunjungan tercatat rapi dan bisa direkap bulanan, (2) antrean tertib dengan layar + suara + struk, (3) beban piket dan penggantian petugas transparan, (4) kepuasan layanan terukur lewat SKM.

## 2. Peran Pengguna (Role)

| Peran | Akses | Login |
|---|---|---|
| Tamu di kantor | Form buku tamu di PC/tablet lobi (mode kios): foto wajib, nomor antrean, cetak struk | Tidak perlu (perangkat kios didaftarkan admin) |
| Tamu online | Form layanan dari luar kantor lewat HP/laptop sendiri: tanpa foto, upload file opsional | Tidak perlu |
| Resepsionis | Daftar antrean hari ini, tombol Panggil/Ulang/Lewati | Username + password |
| Petugas | Antrean miliknya, ubah status layanan, ajukan swap | Username + password |
| Admin | Semua data, jadwal piket, dasbor, ekspor, kelola akun | Username + password |
| Layar Antrean | Halaman publik read-only (TV lobi) | Tidak perlu |

Akun dibuat oleh admin. Resepsionis boleh dirangkap admin (lihat Asumsi A3).

## 3. Ruang Lingkup

**Masuk (MVP):** form dinamis, foto wajib, upload opsional, nomor antrean harian, panggil + TTS, layar antrean, struk, portal petugas, jadwal piket + swap + audit, dasbor, ekspor Excel/CSV, notifikasi pengingat petugas, tautan SKM otomatis.

**Di luar MVP:** pengenalan wajah, integrasi SIPETIR/aplikasi BPS lain, aplikasi mobile native, multi-cabang.

## 4. Alur Utama

1. Tamu membuka form → isi data → pilih Tujuan Layanan → isi detail → ambil foto → setujui pernyataan data pribadi → kirim.
2. Sistem membuat **tiket** + **nomor antrean harian** (reset otomatis tiap hari) dan menampilkan tombol **Cetak** untuk struk.
3. Resepsionis menekan **Panggil** → layar TV menampilkan nomor + suara TTS memanggil.
4. Tiket ditugaskan ke petugas piket hari itu. Petugas melihat profil tamu, nomor, keperluan, lalu menekan **Mulai Layani** dan **Selesai**.
5. Saat **Selesai**, sistem mengirim pesan berisi tautan SKM ke tamu (WA/email).
6. Admin memantau dasbor, mengelola jadwal, dan mengekspor laporan.

**Alur tamu online:** tamu mengisi form dari luar kantor (tanpa foto, upload file opsional) → mendapat kode tiket (mis. `ONL-0001`) tanpa nomor antrean lobi → permintaan muncul di tab "Permintaan Online" petugas piket → petugas menindaklanjuti lewat email/WA dan menandai `Selesai` → tautan SKM terkirim.

## 5. Kebutuhan Fungsional

### 5.1 Portal Tamu (Front-End)
- **FR-01 Multi-step form (wizard):** Langkah 1 data diri, langkah 2 detail layanan, langkah 3 foto + konfirmasi.
- **FR-02 Field wajib awal:** Nama Lengkap, Email, No. HP, Asal Instansi, Tujuan Layanan (PST / Non-PST / Pengaduan).
- **FR-03 Conditional logic:**
  - **PST:** saluran Online/Offline (ditentukan otomatis dari pintu masuk: kios = Offline, form online = Online) → Jenis Layanan (Konsultasi Statistik, Perpustakaan, Rekomendasi Kegiatan Statistik) → Rincian Keperluan → Upload Dokumen Pendukung (opsional; surat tugas, tabel data yang diperlukan).
  - **Non-PST:** dropdown (Konsultasi, Kerjasama, Lainnya) → Rincian Keperluan (textarea).
  - **Pengaduan:** Judul Aduan + Isi Pengaduan (textarea).
- **FR-04 Identity capture:** ambil foto via kamera (`getUserMedia`), preview, ulangi foto; tombol kirim terkunci sampai ada foto. **Hanya di mode kios (tamu di kantor)**; tamu online tidak diminta foto. Perangkat kios harus punya webcam/kamera.
- **FR-05 Validasi:** sisi klien dan server; email valid; no. HP dinormalisasi ke format `62…`.
- **FR-06 Layar sukses (kios):** menampilkan nomor antrean besar + tombol **Cetak Nomor Antrean** (cetak manual lewat tombol, bisa diulang); auto-reset ke form kosong setelah ±30 detik. Layar sukses online menampilkan kode tiket dan informasi bahwa petugas akan menghubungi.
- **FR-07 Responsive:** dioptimalkan untuk tablet (landscape & portrait), target sentuh besar.
- **FR-08 Mode kios:** halaman `/kios` dapat diakses langsung via URL khusus perangkat lobi tanpa kewajiban token perangkat (menghindari kendala saat clear cache), berisi form lengkap dengan foto webcam + antrean harian + tombol cetak struk 58 mm. Pembeda tamu di kantor vs luar kantor berbasis pemisahan rute (`/kios` untuk lobi, `/layanan` untuk luar kantor).
- **FR-09 Form online:** halaman publik `/layanan` (tanpa foto) dengan kolom yang sama, upload file opsional, kode tiket `ONL-xxxx`, tidak masuk antrean lobi/layar.

### 5.2 Antrean & Layar (Kiosk/Signage)
- **FR-10 Nomor antrean:** format `PST-01`, `PST-02`, … (2 digit, otomatis 3 digit setelah 99), satu urutan untuk semua tamu kios, reset otomatis tiap hari pukul 00:00, aman dari duplikat saat input bersamaan (transaksi DB + unique constraint). Tamu online tidak mendapat nomor antrean.
- **FR-11 Panggil/Ulang/Lewati:** oleh resepsionis/admin; setiap panggilan tercatat (`call_events`).
- **FR-12 Layar publik read-only:** nomor sedang dipanggil + 5 panggilan terakhir + jumlah menunggu. **Tidak menampilkan nama atau data pribadi.**
- **FR-13 Update layar:** polling ±3 detik (tanpa WebSocket agar kompatibel cPanel).
- **FR-14 Audio:** Web Speech API bahasa Indonesia (`id-ID`), mengucapkan "Nomor antrean … silakan menuju … ". Ada tombol **Aktifkan Suara** (browser mewajibkan interaksi pengguna sebelum audio boleh diputar).
- **FR-15 Struk:** printer DT 58D (kertas 58 mm) terpasang di PC kios. Menurut deskripsi penjual, koneksi Bluetooth untuk Android/iOS sedangkan Windows lewat USB, jadi di PC gunakan kabel USB. Halaman cetak lebar 58 mm (CSS `@page`, area cetak efektif ±48 mm) berisi nomor, tanggal/jam, jenis layanan. Cetak dipicu tombol **Cetak** di layar sukses; dialog cetak dapat dilewati dengan Chrome `--kiosk-printing`. Opsi lanjutan: ESC/POS.

### 5.3 Portal Petugas
- **FR-20 Antrean saya:** tiket yang ditugaskan, nomor, nama tamu, instansi, keperluan, lampiran, foto.
- **FR-21 Status tiket:** `Menunggu → Dipanggil → Dilayani → Selesai` (cabang: `Tidak Hadir`, `Dibatalkan`). Setiap perubahan dicatat (siapa, kapan).
- **FR-22 Catatan layanan:** petugas dapat menulis catatan hasil layanan.
- **FR-23 Swap petugas:** petugas atau admin mengganti petugas piket pada tanggal tertentu dengan alasan. Data jadwal asli **tidak ditimpa**; swap disimpan sebagai riwayat terpisah.
- **FR-24 Penugasan tiket:** default ke petugas piket hari itu dengan beban tiket aktif paling sedikit; resepsionis dapat mengubah.

### 5.4 Admin (Back-End)
- **FR-30 Daftar kunjungan:** tabel dengan filter (tanggal, layanan, status, petugas), pencarian, detail + foto + lampiran.
- **FR-31 Dasbor:** kunjungan hari ini/minggu/bulan, per jenis layanan, per instansi, rata-rata waktu tunggu dan waktu layanan, tiket belum selesai, SKM terkirim.
- **FR-32 Jadwal piket (CRUD):** tetapkan petugas per tanggal (kalender/tabel), dukung penugasan massal (mis. satu bulan).
- **FR-33 Analitik swap:** siapa paling sering digantikan, siapa paling sering menggantikan, per periode.
- **FR-34 Ekspor:** Excel/CSV untuk daftar kunjungan dan rekap bulanan.
- **FR-35 Kelola akun:** tambah/nonaktifkan pengguna, reset password.

### 5.5 Notifikasi
- **FR-40 Pengingat piket:** setiap hari kerja (mis. 07:00) kirim pesan ke petugas yang bertugas hari itu.
- **FR-41 Tautan SKM:** saat status `Selesai`, kirim pesan ke tamu berisi tautan SKM resmi (URL disimpan di pengaturan/`.env`, bukan hard-coded).
- **FR-42 Pola driver:** lapisan `NotificationService` dengan driver `log`, `mail`, `whatsapp` agar penyedia WA mudah diganti. Semua kiriman dicatat di `notification_logs` (status berhasil/gagal).

## 6. Model Data (ringkas)

| Tabel | Kolom kunci |
|---|---|
| `users` | id, name, username, password, role (`admin`/`resepsionis`/`petugas`), phone, email, is_active |
| `visits` | id, visit_date, channel (`kiosk`/`online`), ticket_code, queue_seq (null untuk online), queue_number (null untuk online), full_name, email, phone, institution, purpose (`pst`/`non_pst`/`pengaduan`), pst_channel, service_type, non_pst_type, detail, complaint_title, photo_path, consent_at, status, assigned_officer_id, called_at, started_at, finished_at, officer_note, skm_sent_at |
| `visit_attachments` | id, visit_id, path, original_name, mime, size |
| `visit_status_logs` | id, visit_id, from_status, to_status, user_id, created_at |
| `call_events` | id, visit_id, called_by, counter_label, created_at |
| `duty_schedules` | id, duty_date, officer_id, created_by — unik (duty_date, officer_id) |
| `duty_swaps` | id, duty_date, original_officer_id, replacement_officer_id, reason, swapped_by, created_at |
| `notification_logs` | id, type, channel, recipient, payload, status, error, created_at |
| `settings` | key, value (mis. `skm_url`, nama kantor, teks struk) |
| `kiosk_devices` | id, name, token_hash, activated_by, last_seen_at, is_active |

Aturan: `queue_number` unik per `visit_date`; jangan pernah `UPDATE` baris jadwal asli untuk swap.

## 7. Kebutuhan Non-Fungsional

- **Kompatibilitas hosting:** berjalan di PC Windows (Laragon/XAMPP) lewat LAN, lalu di cPanel (PHP + MySQL). Target PHP 8.3 dan harus tetap jalan jika cPanel naik ke versi PHP yang lebih baru; PHP 8.2 ke bawah tidak didukung (sejalan arahan tim IT pusat). Tanpa WebSocket, tanpa proses Node di server. Aset front-end di-build di PC lalu di-upload.
- **HTTPS wajib untuk kamera:** kamera browser hanya jalan di HTTPS atau `localhost`. Untuk LAN gunakan sertifikat lokal (mkcert/Laragon SSL) yang dipercaya di perangkat kios, atau konfigurasi kios khusus. Di cPanel gunakan AutoSSL.
- **Keamanan:** hash password, proteksi CSRF, validasi server, rate limiting form tamu, honeypot anti-bot, middleware per peran, foto dan lampiran disimpan **privat** (tidak bisa diakses lewat URL publik), validasi tipe/ukuran file (PDF/JPG/PNG/XLSX/DOCX, maks. 5 MB).
- **Privasi:** foto dan kontak adalah data pribadi (UU PDP No. 27/2022). Sediakan pernyataan persetujuan sebelum kirim, akses data dibatasi sesuai peran, dan retensi foto **1 tahun**: file foto yang lebih dari 365 hari dihapus otomatis oleh tugas terjadwal, sedangkan data teks kunjungan tetap tersimpan (sebaiknya dikonfirmasi ke pimpinan).
- **Kinerja:** halaman tamu dan layar antrean ringan; foto dikompres (maks. ±640 px, JPEG).
- **Keandalan:** backup database terjadwal; layar antrean tetap berfungsi jika koneksi putus sesaat.
- **Zona waktu:** `Asia/Jakarta`. Bahasa antarmuka: Indonesia.

## 8. Arsitektur & Stack

- **Backend:** Laravel (versi stabil terbaru yang mendukung PHP 8.3) + MySQL; `composer.json` mengunci `config.platform.php` ke 8.3 agar paket yang terpasang cocok dengan cPanel
- **Frontend:** Blade + Tailwind CSS + Alpine.js (sederhana, tanpa SPA) — dasbor boleh memakai Chart.js
- **Auth:** session-based Laravel (Breeze), middleware peran
- **Ekspor:** `maatwebsite/excel` (atau CSV native)
- **Penjadwalan:** Laravel Scheduler (cron cPanel / Task Scheduler Windows)
- **Struktur halaman:** `/` dan `/layanan` form online · `/kios` form tamu di kantor (perangkat terdaftar) · `/layar` display antrean · `/resepsionis` · `/petugas` · `/admin`

## 9. Rilis Bertahap

| Rilis | Isi |
|---|---|
| **MVP (v1.0)** | FR-01–09, FR-10–15, FR-20–24, FR-30, FR-32 |
| **v1.1** | FR-31, FR-33–35, FR-40–42 |
| **v1.2** | Penyempurnaan: SKM otomatis penuh, laporan bulanan, backup otomatis, hardening |

## 10. Kriteria Penerimaan (contoh)

- Tamu di kantor (mode kios) tidak bisa mengirim form tanpa foto dan field wajib; tamu online tidak diminta foto.
- Dua tamu mendaftar bersamaan tidak pernah mendapat nomor sama.
- Memilih PST menampilkan Online/Offline + jenis layanan + upload; memilih Pengaduan menampilkan judul + isi.
- Swap petugas membuat baris baru di `duty_swaps`, jadwal asli tetap utuh.
- Layar antrean tidak menampilkan nama tamu; suara terpicu hanya setelah tombol Panggil.
- Foto tidak bisa dibuka lewat URL tanpa login.
- Ekspor Excel memuat kolom sesuai filter yang dipilih.
- `/kios` dapat diakses langsung via link tanpa token registrasi perangkat.
- File foto lebih dari 365 hari terhapus otomatis, sedangkan data teks kunjungan tetap ada.

## 11. Asumsi & Pertanyaan Terbuka

**Sudah terjawab (27 Okt 2026)**

| # | Keputusan |
|---|---|
| A1 | Nomor antrean berformat `PST-01`, `PST-02`, … satu urutan harian untuk tamu kios |
| A2 | Tamu luar kantor mengisi form online tanpa foto (upload file opsional); foto hanya untuk tamu di kantor |
| A5 | Foto disimpan 1 tahun, lalu file dihapus otomatis |
| A7 | Printer DT 58D (58 mm), dipasang di PC kios, dicetak lewat tombol |
| A8 | PHP cPanel kantor 8.3, kemungkinan di-upgrade; kode wajib kompatibel |

**Masih terbuka**

| # | Asumsi saat ini | Perlu dikonfirmasi |
|---|---|---|
| A3 | Resepsionis dipisah dari admin tetapi boleh dirangkap | Siapa yang menjadi resepsionis? |
| A4 | 1–3 petugas piket per hari | Jumlah sebenarnya? |
| A6 | WA gateway belum dipilih; fase awal driver `log`/email | Penyedia WA dan nomor pengirim khusus kantor |
| A9 | Pembeda kios vs online lewat alamat `/kios` yang hanya bisa dibuka dari perangkat terdaftar admin | Setuju? Alternatif: batasi IP kantor (kurang andal bila IP berubah) |
| A10 | Tamu online mendapat kode tiket `ONL-xxxx`, tanpa nomor antrean/layar/struk, ditangani petugas lewat email/WA; semua tujuan layanan boleh online; pilihan Online/Offline pada PST ditentukan otomatis oleh pintu masuk | Setuju? |
| A11 | Perangkat kios adalah PC dengan webcam dan printer USB; tablet opsional | PC kios punya webcam? Tablet tetap dipakai? |

## 12. Glosarium Istilah IT

- **Wizard / multi-step form:** form dipecah beberapa langkah.
- **Conditional logic:** isian muncul/hilang sesuai pilihan sebelumnya.
- **Role-based access control (RBAC):** hak akses menurut peran.
- **CRUD:** Create, Read, Update, Delete.
- **Audit trail:** riwayat perubahan yang tidak menimpa data asli.
- **Polling:** halaman menanyakan data terbaru ke server secara berkala.
- **TTS (Text-to-Speech):** teks diubah menjadi suara.
- **Kiosk mode:** browser layar penuh yang terkunci pada satu halaman.
- **Scheduler / cron:** tugas otomatis terjadwal.
- **Driver pattern:** satu antarmuka, banyak penyedia yang bisa ditukar.
