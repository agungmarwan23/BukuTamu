# AGENTS.md — Aturan Proyek Buku Tamu BPS Kab. Indragiri Hilir

## Konteks
Aplikasi web buku tamu + antrean + penjadwalan petugas piket untuk BPS Kab. Indragiri Hilir.
Dokumen wajib dibaca sebelum bekerja:
1. `PROJECT_LOG.md` — status terakhir, keputusan, kegagalan, langkah berikutnya
2. `docs/PRD.md` — kebutuhan lengkap (ID FR-xx)
3. `docs/PROMPT_PLAYBOOK.md` — fase kerja (hanya jika perlu)

## Stack (sudah diputuskan, jangan diganti tanpa persetujuan)
- Laravel (versi stabil terbaru yang mendukung PHP 8.3) + MySQL, Blade + Tailwind CSS + Alpine.js
- PHP minimum 8.3 (cPanel kantor). Kode harus tetap kompatibel jika cPanel naik versi; jangan mengandalkan PHP 8.2 ke bawah
- Auth session-based dengan peran: `admin`, `resepsionis`, `petugas`
- Target jalan: Windows (Laragon) via LAN, lalu cPanel (shared hosting PHP + MySQL)

## Batasan kompatibilitas cPanel (penting)
- Dilarang memakai WebSocket, Redis, Supervisor, atau proses Node di server. Gunakan polling.
- Aset front-end di-build di PC (`npm run build`), hasilnya di `public/build` ikut di-deploy.
- Jangan memakai package yang butuh ekstensi PHP langka tanpa memberi tahu.
- Tugas terjadwal memakai Laravel Scheduler (satu cron `schedule:run`).

## Aturan kerja
- Kerjakan **satu fase per sesi**, sesuai `docs/PROMPT_PLAYBOOK.md`. Jangan melebar ke fase lain.
- Buat rencana implementasi dulu, tunggu persetujuan, baru menulis kode.
- Jika ada yang ambigu atau tidak ada di PRD: **tanya**, jangan menebak.
- Kode dan nama variabel/tabel dalam bahasa Inggris; teks antarmuka dalam bahasa Indonesia.
- Zona waktu `Asia/Jakarta`. Semua tanggal disimpan konsisten.
- Wajib: migration + seeder (data demo), validasi di server (Form Request), middleware peran, dan tes untuk logika kritis (nomor antrean, swap, status tiket).
- Foto dan lampiran disimpan **privat** (bukan di `public/`), diakses hanya lewat route yang terautentikasi.
- Foto hanya untuk tamu di kantor (route `/kios`, perangkat terdaftar). Form online `/layanan` tidak meminta foto. File foto dihapus otomatis setelah 365 hari, data teks tetap.
- Layar antrean publik tidak boleh menampilkan nama atau data pribadi tamu.
- Jadwal piket asli tidak pernah ditimpa; swap disimpan di `duty_swaps`.
- Jangan menaruh rahasia (API key, password) di kode. Pakai `.env` dan perbarui `.env.example`.
- Jangan menghapus atau mereset database yang berisi data tanpa persetujuan eksplisit.
- Commit kecil dan sering dengan pesan jelas, satu commit per unit kerja yang utuh.

## Wajib di akhir setiap sesi
Perbarui `PROJECT_LOG.md`: apa yang dikerjakan, hasil (berhasil/gagal), file penting yang berubah, masalah + solusi, dan langkah berikutnya. Tandai status fase di tabel progres.
