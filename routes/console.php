<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat piket petugas PST setiap hari kerja (Senin s.d. Jumat) pukul 07:00 WIB (FR-40)
Schedule::command('duty:send-reminders')
    ->weekdays()
    ->at('07:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Backup database harian pukul 01:00 WIB (simpan 7 hari terakhir)
Schedule::command('db:backup --keep=7')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Hapus foto kunjungan yang lebih dari 365 hari, pukul 02:00 WIB (PRD: retensi 1 tahun)
Schedule::command('photos:purge-expired')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
