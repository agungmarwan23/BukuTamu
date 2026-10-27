<?php

use App\Http\Controllers\GuestVisitController;
use App\Http\Controllers\KioskDeviceController;
use App\Http\Controllers\ProfileController;
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

// Redirect Dasbor Berdasarkan Peran
Route::get('/dashboard', function () {
    $user = Auth::user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'resepsionis' => redirect()->route('resepsionis.dashboard'),
        default => redirect()->route('petugas.dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

// Dasbor Peran
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'role:resepsionis,admin'])->prefix('resepsionis')->name('resepsionis.')->group(function () {
    Route::get('/', function () {
        return view('resepsionis.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', function () {
        return view('petugas.dashboard');
    })->name('dashboard');
});

// Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
