# Buku Tamu & Antrean Layanan BPS Kab. Indragiri Hilir

Sistem Informasi Buku Tamu Digital, Antrean Pelayanan Statistik Terpadu (PST), Penjadwalan Petugas Piket, dan Notifikasi untuk **Badan Pusat Statistik (BPS) Kabupaten Indragiri Hilir**.

---

## 1. Daftar Rute & Modul Aplikasi

| Rute / Modul | URL | Hak Akses | Keterangan |
|---|---|---|---|
| **Form Layanan Online** | `/layanan` (atau `/`) | Publik (Luar Kantor) | Formulir tamu online tanpa foto, upload dokumen pendukung opsional, menghasilkan kode tiket unik (`ONL-xxxx`). |
| **Kios Lobi Kantor** | `/kios` | Terbuka Langsung | Formulir khusus lobi kantor: pengambilan foto via webcam, cetak struk 58 mm, nomor antrean lobi (`PST-01`). Tanpa kewajiban token aktivasi perangkat. |
| **Layar TV Antrean** | `/layar` | Publik (Display Lobi) | Layar display panggilan nomor antrean lobi dengan audio Text-to-Speech (TTS) bahasa Indonesia, tanpa menampilkan data pribadi tamu. |
| **Dasbor Resepsionis** | `/resepsionis` | Resepsionis & Admin | Panggil nomor antrean berikutnya, panggil ulang, lewati, atau alihkan penugasan tiket ke petugas lain. |
| **Portal Petugas** | `/petugas` | Petugas & Admin | Tab *Antrean Saya*, *Permintaan Online*, *Jadwal Piket Saya*, dan *Tukar (Swap) Piket*. |
| **Panel Administrator** | `/admin` | Admin | Dasbor analitik, daftar kunjungan + audit trail + ekspor CSV, kelola jadwal piket + penugasan massal + impor CSV, analitik swap, kelola akun, dan kelola perangkat kios. |

---

## 2. Panduan Instalasi Lokal (Laragon / PC Windows)

### Prasyarat Sistem:
- **PHP**: Versi 8.3 (target kompatibel dengan cPanel BPS)
- **Database**: MySQL 8.0+ atau MariaDB 10.4+
- **Composer**: Versi 2.x
- **Node.js**: LTS (v20+ atau v22+) & NPM
- **Web Server**: Apache / Nginx bawaan Laragon

### Langkah Instalasi:
1. Clone / letakkan repositori di `D:\Ngoding\BukuTamu`.
2. Salin file konfigurasi lingkungan:
   ```bash
   cp .env.example .env
   ```
3. Sesuaikan konfigurasi database di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=buku_tamu
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Pasang dependensi PHP dan Node:
   ```bash
   composer install
   npm install
   ```
5. Buat application key dan jalankan migrasi serta seeder:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
   *Akun bawaan seeder:*
   - Admin: `admin` (password: `password`)
   - Resepsionis: `resepsionis` (password: `password`)
   - Petugas: `petugas1`, `petugas2`, `petugas3` (password: `password`)
6. Kompilasi aset front-end:
   ```bash
   npm run build
   ```
7. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

---

## 3. Konfigurasi HTTPS Lokal untuk Kamera di Jaringan LAN

Peramban modern (Chrome/Edge/Firefox) **mewajibkan protokol HTTPS** agar fitur webcam (`navigator.mediaDevices.getUserMedia`) diizinkan aktif, kecuali jika dibuka langsung via `http://localhost`. Ketika PC kios atau tablet lobi mengakses aplikasi melalui IP LAN (contoh: `http://192.168.1.50:8000`), kamera akan terblokir oleh kebijakan keamanan peramban.

### Solusi 1: Mengaktifkan SSL Otomatis di Laragon (Direkomendasikan)
1. Buka Laragon -> Klik Kanan -> **Apache** -> **SSL** -> Centang **Enabled**.
2. Laragon akan otomatis membuat sertifikat SSL root lokal (*Laragon CA*).
3. Pasang hostname lokal di Virtual Host (contoh: `bukutamu.test`).
4. Akses melalui `https://bukutamu.test`.

### Solusi 2: Menggunakan `mkcert` (Sertifikat Tepercaya di LAN)
1. Unduh `mkcert` dari [GitHub Releases](https://github.com/FiloSottile/mkcert/releases).
2. Di command prompt Administrator:
   ```cmd
   mkcert -install
   mkcert 192.168.1.50 bukutamu.local localhost 127.0.0.1
   ```
3. Pasang sertifikat (`.pem`) dan key (`-key.pem`) pada web server Laragon.
4. **Mempercayai sertifikat di PC Kios / Tablet:**
   - Salin file `rootCA.pem` (dapat dicari via `mkcert -CAROOT`) ke PC Kios atau Tablet.
   - Di Windows PC Kios: Klik ganda `rootCA.pem` -> *Install Certificate* -> *Local Machine* -> Pilih *Trusted Root Certification Authorities*.
   - Di Android Tablet: Masuk *Settings* -> *Security* -> *Install from storage* -> *CA certificate*.

### Solusi 3: Pengecualian Chrome Flags (Khusus Pengujian/PC Kios Fisik)
Jika tidak ingin membuat sertifikat SSL, tambahkan IP LAN ke daftar pengecualian keamanan peramban pada PC Kios:
1. Buka peramban Chrome/Edge di PC Kios.
2. Buka URL: `chrome://flags/#unsafely-treat-insecure-origin-as-secure`
3. Masukkan origin server: `http://192.168.1.50:8000` (sesuaikan dengan IP host).
4. Ubah dropdown menjadi **Enabled** lalu klik **Relaunch**.
5. Akses kamera akan langsung diizinkan di alamat tersebut tanpa HTTPS.

---

## 4. Konfigurasi Mode Kios Chrome (PC Lobi & Layar TV)

Untuk menjaga tampilan tetap layar penuh (*fullscreen*) dan mencegah pengunjung menutup aplikasi:

### A. Untuk PC Kios Lobi (Auto Print Struk):
Gunakan flag `--kiosk` dan `--kiosk-printing` agar peramban langsung mencetak ke printer default tanpa menampilkan dialog konfirmasi print:
```cmd
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --kiosk-printing "http://localhost:8000/kios"
```

### B. Untuk Monitor / Layar TV Antrean:
Jalankan Chrome dalam mode fullscreen di layar kedua/TV:
```cmd
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk "http://localhost:8000/layar"
```
*Catatan Audio TV:* Karena kebijakan autoplay peramban modern, klik tombol **"Aktifkan Suara"** sekali saat layar antrean dinyalakan di pagi hari agar suara panggilan Text-to-Speech (TTS) dapat berbunyi otomatis.

---

## 5. Konfigurasi Printer Struk Thermal 58 mm (DT 58D via USB)

1. **Koneksi Fisik**:
   - Hubungkan printer DT 58D ke PC Kios lobi menggunakan **kabel USB** (bukan Bluetooth; Bluetooth khusus HP).
   - Pastikan kabel daya terpasang dan kertas thermal 58 mm terpasang menghadap ke atas.
2. **Driver Windows**:
   - Pasang driver printer thermal POS 58 mm (bawaan printer atau driver standar *Generic / Text Only* atau *POS-58*).
   - Masuk ke **Settings -> Devices -> Printers & Scanners** di Windows.
   - Tetapkan printer thermal ini sebagai **Default Printer**.
3. **Pengaturan Kertas**:
   - Klik kanan printer -> *Printing Preferences* -> *Layout/Advanced*.
   - Pilih ukuran kertas: `58mm x Receipt` (lebar efektif cetak ±48 mm).
   - Set margin ke `None` atau `0 mm`.
4. **Uji Cetak (Test Print)**:
   - Klik tombol *Print Test Page* di Windows untuk memastikan teks tercetak jelas dan tidak terpotong.
   - Pada aplikasi, setelah tamu mengisi form di `/kios`, klik tombol **Cetak Struk Antrean** di layar sukses.

---

## 6. Akses Langsung Kios Lobi Kantor

Untuk mempermudah operasional lobi tanpa risiko token hilang saat clear cache atau incognito browser:
- Halaman `/kios` dapat **langsung dibuka** di peramban PC Kios lobi.
- Formulir lobi tetap dilengkapi penangkapan foto webcam, penomoran antrean harian lobi (`PST-01`), dan pencetakan struk thermal 58 mm.
- Menu manajemen perangkat di `/admin/perangkat` dan perintah Artisan `kiosk:register` tetap disediakan sebagai inventaris opsional perangkat kantor.

---

## 7. Retensi Foto Otomatis 365 Hari (UU PDP & PRD FR-05)

Sesuai amanat UU PDP No. 27/2022, foto tamu disimpan maksimal **1 tahun (365 hari)**:
- Berkas fisik foto di storage privat dihapus otomatis.
- Kolom `photo_path` pada database diatur menjadi `null`.
- **Seluruh data teks riwayat kunjungan tetap tersimpan utuh** untuk keperluan statistik dan laporan tahunan BPS.

### Perintah Artisan:
```bash
# Preview foto yang akan dihapus tanpa menghapus (uji coba)
php artisan photos:purge-expired --dry-run

# Jalankan penghapusan foto di atas 365 hari
php artisan photos:purge-expired

# Custom retensi (misal: uji coba 30 hari)
php artisan photos:purge-expired --days=30
```
*Jadwal Otomatis:* Perintah ini dijalankan setiap hari pukul 02:00 WIB oleh scheduler.

---

## 8. Backup Database Otomatis

Aplikasi dilengkapi perintah backup native yang mengekspor seluruh basis data ke file SQL terkompresi di folder privat `storage/app/backups`:

```bash
# Backup database saat ini
php artisan db:backup

# Tentukan retensi simpan file backup (default: simpan 7 hari terakhir)
php artisan db:backup --keep=14
```
*Fitur Command:*
- Mendeteksi path binary `mysqldump` secara otomatis (kompatibel dengan Laragon, XAMPP, maupun shared hosting cPanel).
- Menggunakan parameter `--single-transaction` dan `--quick` agar tidak mengunci transaksi tabel aktif.
- Menghapus file backup lama secara otomatis sesuai batas `--keep`.
*Jadwal Otomatis:* Dijalankan setiap hari pukul 01:00 WIB oleh scheduler.

---

## 9. Penjadwalan Tugas (Laravel Scheduler)

Tugas terjadwal (Pengingat piket 07:00 WIB, Backup DB 01:00 WIB, Retensi foto 02:00 WIB) dijalankan lewat `schedule:run`.

### A. Windows Task Scheduler (PC Kantor / LAN)
1. Buka **Task Scheduler** di Windows (`taskschd.msc`).
2. Buat task baru: `BukuTamu_Scheduler`.
3. Trigger: Daily, ulangi setiap **1 menit** untuk selamanya.
4. Action:
   - Program: `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `D:\Ngoding\BukuTamu`

### B. cPanel Cron Job (Server Produksi)
Tambahkan cron job dengan frekuensi tiap menit (`* * * * *`):
```bash
/usr/local/bin/php /home/username_cpanel/buku_tamu/artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Pengujian Otomatis & Kesiapan Rilis

Seluruh logika bisnis, otorisasi RBAC, alur antrean, integritas swap, retensi foto, dan header keamanan diuji secara menyeluruh:

```bash
php artisan test
```

### Ringkasan Status Kriteria Penerimaan (PRD Bagian 10):
- **AC-1** (Foto wajib di kios, foto tidak diminta di form online): **LULUS** (`test_ac_01`)
- **AC-2** (Penomoran antrean berurutan PST-01, PST-02 tanpa duplikat): **LULUS** (`test_ac_02`)
- **AC-3** (Kondisional layanan PST vs Pengaduan): **LULUS** (`test_ac_03`)
- **AC-4** (Swap piket tidak mengubah jadwal asli di `duty_schedules`): **LULUS** (`test_ac_04`)
- **AC-5** (Layar TV dan API `/api/layar` tidak membocorkan nama/kontak tamu): **LULUS** (`test_ac_05`)
- **AC-6** (Akses foto/lampiran privat wajib terautentikasi): **LULUS** (`test_ac_06`)
- **AC-7** (Ekspor CSV sesuai filter aktif): **LULUS** (`test_ac_07`)
- **AC-8** (Rute `/kios` dapat diakses langsung via URL tanpa token registrasi): **LULUS** (`test_ac_08`)
- **AC-9** (File foto > 365 hari terhapus otomatis, data teks tetap utuh): **LULUS** (`test_ac_09`)
- **AC-10** (Security Headers dasar X-Content-Type-Options, X-Frame-Options, CSP/Permissions-Policy terpasang): **LULUS** (`test_ac_10`)

**Hasil Pengujian Terakhir:** 115 tests passed, 415 assertions (100% Lulus).
