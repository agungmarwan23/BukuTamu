<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DutyScheduleController as AdminDutyScheduleController;
use App\Http\Controllers\Admin\KioskDeviceManageController as AdminKioskDeviceController;
use App\Http\Controllers\Admin\SwapAnalyticsController as AdminSwapAnalyticsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VisitController as AdminVisitController;
use App\Http\Controllers\GuestVisitController;
use App\Http\Controllers\KioskDeviceController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\VisitMediaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Buku Tamu BPS Kab. Indragiri Hilir
|--------------------------------------------------------------------------
*/

// Sesuai PRD FR-09: form online di /layanan (dan / mengarah ke sini)
Route::redirect('/', '/layanan');

// Form Layanan Online (publik dari luar kantor)
Route::prefix('layanan')->name('layanan.')->group(function () {
    Route::get('/', [GuestVisitController::class, 'onlineForm'])->name('form');
    Route::post('/', [GuestVisitController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');
    Route::get('/sukses/{ticket_code}', [GuestVisitController::class, 'onlineSuccess'])->name('success');
});

// Aktivasi Perangkat Kios (sebelum middleware proteksi)
Route::get('/kios/activate/{token}', [KioskDeviceController::class, 'activate'])->name('kios.activate');

// Mode Kios Lobi (hanya dapat diakses perangkat terdaftar)
Route::middleware('kiosk.device')->prefix('kios')->name('kios.')->group(function () {
    Route::get('/', [GuestVisitController::class, 'kioskForm'])->name('index');
    Route::post('/', [GuestVisitController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('store');
    Route::get('/sukses/{ticket_code}', [GuestVisitController::class, 'kioskSuccess'])->name('success');
});

// Layar TV Antrean (publik, read-only) — FR-12
Route::get('/layar', [QueueController::class, 'screen'])->name('layar');

// API Publik Layar TV (polling tiap 3 detik) — FR-13
Route::get('/api/layar', [QueueController::class, 'screenApi'])->name('api.layar');

// Redirect Dasbor Berdasarkan Peran
Route::get('/dashboard', function () {
    $user = Auth::user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'resepsionis' => redirect()->route('resepsionis.dashboard'),
        default => redirect()->route('petugas.index'),
    };
})->middleware(['auth'])->name('dashboard');

// Akses Media Privat Kunjungan (hanya petugas, resepsionis, admin yang terautentikasi)
Route::middleware(['auth', 'role:admin,resepsionis,petugas'])->group(function () {
    Route::get('/kunjungan/{visit}/foto', [VisitMediaController::class, 'photo'])->name('visit.photo');
    Route::get('/kunjungan/lampiran/{attachment}', [VisitMediaController::class, 'attachment'])->name('visit.attachment');
});

// Dasbor & Panel Admin (FR-30 sampai FR-35)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Daftar Kunjungan, Detail, Ekspor, dan Impor (FR-30, FR-34)
    Route::get('/kunjungan', [AdminVisitController::class, 'index'])->name('visits.index');
    Route::get('/kunjungan/ekspor', [AdminVisitController::class, 'export'])->name('visits.export');
    Route::get('/kunjungan/template', [AdminVisitController::class, 'downloadTemplate'])->name('visits.template');
    Route::post('/kunjungan/import', [AdminVisitController::class, 'importCsv'])->name('visits.import');
    Route::get('/kunjungan/{visit}', [AdminVisitController::class, 'show'])->name('visits.show');

    // Jadwal Piket & Penugasan Massal (FR-32)
    Route::get('/jadwal', [AdminDutyScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/jadwal/template', [AdminDutyScheduleController::class, 'downloadTemplate'])->name('schedules.template');
    Route::post('/jadwal/import', [AdminDutyScheduleController::class, 'importCsv'])->name('schedules.import');
    Route::post('/jadwal', [AdminDutyScheduleController::class, 'store'])->name('schedules.store');
    Route::post('/jadwal/mass-assign', [AdminDutyScheduleController::class, 'massAssign'])->name('schedules.mass_assign');
    Route::delete('/jadwal/{schedule}', [AdminDutyScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Analitik Swap (FR-33)
    Route::get('/analitik-swap', [AdminSwapAnalyticsController::class, 'index'])->name('analytics.swaps');

    // Kelola Akun Pengguna (FR-35)
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [AdminUserController::class, 'store'])->name('users.store');
    Route::post('/pengguna/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/pengguna/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset_password');

    // Kelola Perangkat Kios (FR-01 / Task 7)
    Route::get('/perangkat', [AdminKioskDeviceController::class, 'index'])->name('kiosks.index');
    Route::post('/perangkat', [AdminKioskDeviceController::class, 'store'])->name('kiosks.store');
    Route::post('/perangkat/{device}/regenerate-token', [AdminKioskDeviceController::class, 'regenerateToken'])->name('kiosks.regenerate');
    Route::post('/perangkat/{device}/toggle', [AdminKioskDeviceController::class, 'toggleStatus'])->name('kiosks.toggle');
    Route::delete('/perangkat/{device}', [AdminKioskDeviceController::class, 'destroy'])->name('kiosks.destroy');
});

// Resepsionis — Antrean & Pemanggilan (FR-11)
Route::middleware(['auth', 'role:resepsionis,admin'])->prefix('resepsionis')->name('resepsionis.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('dashboard');
    Route::post('/panggil/{visit}', [QueueController::class, 'call'])->name('call');
    Route::post('/ulang/{visit}', [QueueController::class, 'recall'])->name('recall');
    Route::post('/lewati/{visit}', [QueueController::class, 'skip'])->name('skip');
    Route::post('/reassign/{visit}', [QueueController::class, 'reassign'])->name('reassign');
    Route::get('/api/antrean', [QueueController::class, 'queueData'])->name('api.queue');
});

// Portal Petugas — Antrean Saya, Permintaan Online, Jadwal & Swap (FR-20 s.d. FR-24)
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', [PetugasController::class, 'index'])->name('dashboard');
    Route::get('/antrean', [PetugasController::class, 'index'])->name('index');
    Route::post('/kunjungan/{visit}/start', [PetugasController::class, 'startService'])->name('start');
    Route::post('/kunjungan/{visit}/finish', [PetugasController::class, 'finishService'])->name('finish');
    Route::post('/kunjungan/{visit}/status', [PetugasController::class, 'updateStatus'])->name('status');
    Route::post('/online/{visit}/status', [PetugasController::class, 'updateOnlineStatus'])->name('online.status');
    Route::post('/swap', [PetugasController::class, 'storeSwap'])->name('swap.store');
});

// Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

