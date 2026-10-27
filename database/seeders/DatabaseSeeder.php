<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Administrator
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator BPS',
                'email' => 'admin@bps-inhil.go.id',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. Akun Resepsionis
        User::updateOrCreate(
            ['username' => 'resepsionis'],
            [
                'name' => 'Resepsionis PST',
                'email' => 'resepsionis@bps-inhil.go.id',
                'phone' => '081234567891',
                'password' => Hash::make('password'),
                'role' => 'resepsionis',
                'is_active' => true,
            ]
        );

        // 3. Tiga Akun Petugas
        $officers = [
            [
                'username' => 'petugas1',
                'name' => 'Petugas PST 1 (Ahmad)',
                'email' => 'petugas1@bps-inhil.go.id',
                'phone' => '081234567892',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'is_active' => true,
            ],
            [
                'username' => 'petugas2',
                'name' => 'Petugas PST 2 (Siti)',
                'email' => 'petugas2@bps-inhil.go.id',
                'phone' => '081234567893',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'is_active' => true,
            ],
            [
                'username' => 'petugas3',
                'name' => 'Petugas PST 3 (Budi)',
                'email' => 'petugas3@bps-inhil.go.id',
                'phone' => '081234567894',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'is_active' => true,
            ],
        ];

        foreach ($officers as $officer) {
            User::updateOrCreate(
                ['username' => $officer['username']],
                $officer
            );
        }

        // 4. Pengaturan Sistem Default (skm_url kosong sesuai PRD)
        $defaultSettings = [
            'skm_url' => '',
            'office_name' => 'BPS Kabupaten Indragiri Hilir',
            'office_address' => 'Jl. Veteran No. 1, Tembilahan, Kab. Indragiri Hilir, Riau',
            'receipt_header' => 'Badan Pusat Statistik Kab. Indragiri Hilir',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda di PST BPS Kab. Indragiri Hilir.',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::setValue($key, $value);
        }
    }
}
