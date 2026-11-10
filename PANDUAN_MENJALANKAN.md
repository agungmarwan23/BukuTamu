# Panduan Menjalankan Web Buku Tamu & Antrean BPS Inhil

---

## ⚡ CARA CEPAT: 1-Klik Langsung Jalan (Paling Mudah)

Anda tidak perlu membuka terminal atau mengetik perintah apa pun secara manual!

### Cukup lakukan salah satu cara berikut:
1. **Lewat Desktop**: Klik ganda (double-click) ikon shortcut **`Buku Tamu BPS Inhil`** yang ada di Desktop Anda.
2. **Lewat Folder**: Buka folder `D:\Ngoding\BukuTamu`, lalu klik ganda file **`JALANKAN_WEB.bat`**.

**Yang otomatis dilakukan oleh file launcher ini:**
1. Otomatis mendeteksi PHP 8.3 & MySQL dari Laragon.
2. Otomatis memastikan database MySQL aktif di latar belakang.
3. Otomatis menjalankan server web Laravel di port 8000.
4. **Otomatis membuka browser Anda langsung ke alamat `http://localhost:8000`**.

*(Biarkan jendela terminal hitam tetap terbuka selama web digunakan. Jika ingin mematikan web, cukup tutup jendela terminal tersebut atau tekan `Ctrl + C`).*

---

## 1. Prasyarat Sistem

Sebelum memulai, pastikan perangkat lunak berikut telah terpasang:
- **PHP**: Versi 8.3 (sudah aktif di terminal atau Laragon)
- **Composer**: Versi 2.x
- **Node.js & NPM**: Versi LTS (v20+ atau v22+)
- **MySQL / MariaDB**: MySQL 8.0+ atau MariaDB 10.4+ (tersedia di Laragon/XAMPP)
- **Web Server**: Apache/Nginx (bawaan Laragon) atau Artisan CLI Server

---

## 2. Langkah Persiapan Pertama Kali (Setup Awal)

Lakukan langkah-langkah ini jika proyek baru pertama kali di-clone atau dipindahkan ke komputer baru:

### Langkah 1: Buka Terminal di Folder Proyek
Buka PowerShell / Command Prompt / Terminal Laragon lalu arahkan ke direktori proyek:
```bash
cd D:\Ngoding\BukuTamu
```

### Langkah 2: Buat Database di MySQL
1. Buka MySQL client (HeidiSQL, phpMyAdmin, atau Navicat).
2. Buat sebuah database baru dengan nama:
   ```sql
   CREATE DATABASE buku_tamu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

### Langkah 3: Konfigurasi File Lingkungan (`.env`)
Salin file konfigurasi contoh `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
*(Di Command Prompt Windows jika `cp` tidak jalan, gunakan: `copy .env.example .env`)*

Buka file `.env` dengan editor teks dan pastikan pengaturan basis data sesuai:
```env
APP_NAME="Buku Tamu BPS Inhil"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=buku_tamu
DB_USERNAME=root
DB_PASSWORD=
```
*(Ubah `DB_PASSWORD` jika MySQL Anda menggunakan kata sandi).*

### Langkah 4: Pasang Dependensi PHP (Composer)
Unduh seluruh library Laravel:
```bash
composer install
```

### Langkah 5: Pasang Dependensi Front-end (NPM)
Unduh seluruh pustaka UI (Tailwind CSS, Alpine.js, Chart.js):
```bash
npm install
```

### Langkah 6: Generate App Key
Buat kunci enkripsi aplikasi Laravel:
```bash
php artisan key:generate
```

### Langkah 7: Jalankan Migrasi & Data Demo (Seeder)
Buat struktur tabel dan isi akun bawaan serta contoh data layanan:
```bash
php artisan migrate --seed
```

> [!NOTE]
> Jika sewaktu-waktu ingin mereset seluruh data kembali ke kondisi demo awal:
> `php artisan migrate:fresh --seed`

### Langkah 8: Kompilasi Aset Tampilan (Build Aset)
Bangun file CSS dan JS untuk produksi:
```bash
npm run build
```

---

## 3. Menjalankan Aplikasi Sehari-hari

Untuk menjalankan aplikasi setelah proses persiapan di atas selesai:

### Cara 1: Menggunakan Perintah Artisan (Paling Mudah)
1. **Pastikan MySQL berjalan** (di Laragon, klik tombol **Start All**).
2. Di terminal folder proyek, jalankan server Laravel:
   ```bash
   php artisan serve
   ```
3. Buka browser dan akses alamat:
   ```
   http://localhost:8000
   ```

*(Opsional untuk pengembangan UI / modifikasi CSS)*:
Jika Anda sedang mengubah tampilan atau kode Blade/Tailwind dan ingin fitur auto-reload, buka tab terminal baru dan jalankan:
```bash
npm run dev
```

### Cara 2: Menggunakan Virtual Host Bawaan Laragon
Jika menggunakan Laragon dengan fitur Virtual Host otomatis:
1. Pastikan folder proyek berada di direktori Laragon (atau tambahkan link di menu *Virtual Hosts*).
2. Mulai Apache dan MySQL (**Start All** di Laragon).
3. Akses via alamat hostname lokal:
   ```
   http://bukutamu.test
   ```

---

## 4. Daftar Akun Pengguna Bawaan (Default Login)

Untuk masuk ke halaman terproteksi, buka URL `/login` menggunakan akun demo berikut:

| Peran (Role) | Username | Password | Hak Akses Utama |
|---|---|---|---|
| **Admin** | `admin` | `password` | Akses penuh, kelola jadwal piket, ekspor CSV, manajemen user & kios |
| **Resepsionis** | `resepsionis` | `password` | Pemanggilan nomor antrean, lewati, transfer tiket |
| **Petugas PST** | `petugas1` | `password` | Melayani antrean di bilik, selesai, swap piket |
| **Petugas PST** | `petugas2` | `password` | Melayani antrean di bilik, selesai, swap piket |
| **Petugas PST** | `petugas3` | `password` | Melayani antrean di bilik, selesai, swap piket |

---

## 5. Daftar Halaman & Modul Aplikasi

| Modul | Alamat URL | Akses | Fungsi |
|---|---|---|---|
| **Form Tamu Online** | `http://localhost:8000/` atau `/layanan` | Publik | Tamu luar kantor mengisi form tanpa foto, mendapat kode tiket `ONL-xxxx` |
| **Kios Tamu Lobi** | `http://localhost:8000/kios` | Terbuka Langsung | Form khusus tablet/PC lobi: foto via webcam, nomor antrean lobi `PST-01`, cetak struk |
| **Layar TV Antrean** | `http://localhost:8000/layar` | Display Monitor TV | Tampilan display antrean realtime + suara panggil Text-to-Speech |
| **Dasbor Resepsionis** | `http://localhost:8000/resepsionis` | Resepsionis & Admin | Tombol panggil nomor berikutnya, panggil ulang, status bilik |
| **Portal Petugas** | `http://localhost:8000/petugas` | Petugas & Admin | Tab antrean saya, mulai layani, selesai layani, pengajuan swap piket |
| **Panel Admin** | `http://localhost:8000/admin` | Khusus Admin | Statistik kunjungan, audit trail, ekspor data, penugasan piket |

---

## 6. Akses Langsung Kios Lobi (`/kios`)

Halaman `/kios` **dapat langsung dibuka** di peramban PC atau tablet yang diletakkan di lobi kantor BPS:
- **Alamat URL**: `http://localhost:8000/kios`
- **Tidak perlu aktivasi token**: Anda tidak perlu mendaftarkan perangkat atau token cookie lagi. Siapa pun di PC lobi cukup membuka URL tersebut atau memasukkannya ke *Bookmark* peramban.
- **Fitur Kios**: Formulir lobi dilengkapi pengambilan foto kehadiran melalui kamera/webcam, penomoran antrean lobi otomatis (`PST-01`, `PST-02`, dst.), serta tombol cetak struk antrean 58 mm.

---

## 7. Langkah Opsional & Fitur Otomatis

### Menjalankan Pengujian Sistem (Automated Tests)
Pastikan semua fungsi dan logika bisnis berjalan 100%:
```bash
php artisan test
```

### Menjalankan Scheduler (Tugas Terjadwal)
Untuk menjalankan pengingat piket (07:00 WIB), backup database harian (01:00 WIB), dan pembersihan retensi foto 365 hari (02:00 WIB):
```bash
php artisan schedule:run
```
*(Di lingkungan server/kantor, perintah ini didaftarkan di Windows Task Scheduler atau Cron cPanel).*

