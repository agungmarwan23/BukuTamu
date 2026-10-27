<?php

namespace App\Console\Commands;

use App\Models\KioskDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterKioskDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kiosk:register {name : Nama atau lokasi perangkat kios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendaftarkan perangkat kios baru dan menghasilkan URL aktivasi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $rawToken = Str::random(48);
        $tokenHash = hash('sha256', $rawToken);

        $device = KioskDevice::create([
            'name' => $name,
            'token_hash' => $tokenHash,
            'is_active' => true,
        ]);

        $activationUrl = url("/kios/activate/{$rawToken}");

        $this->info("Perangkat kios berhasil didaftarkan!");
        $this->table(
            ['ID', 'Nama Perangkat', 'Status'],
            [[$device->id, $device->name, $device->is_active ? 'Aktif' : 'Nonaktif']]
        );

        $this->newLine();
        $this->comment("Buka URL aktivasi berikut di browser perangkat kios:");
        $this->line("<fg=cyan>{$activationUrl}</>");
        $this->newLine();
        $this->comment("Setelah dibuka, cookie perangkat aman akan disimpan dan kios siap digunakan.");

        return Command::SUCCESS;
    }
}
