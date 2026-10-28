<?php

namespace App\Console\Commands;

use App\Services\DutyScheduleService;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDutyRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duty:send-reminders 
                            {--date= : Tanggal piket yang akan diingatkan (format: Y-m-d, default: hari ini)}
                            {--force : Paksa pengiriman meskipun di akhir pekan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pengingat jadwal piket PST harian kepada petugas piket efektif (memperhitungkan swap)';

    /**
     * Execute the console command.
     */
    public function handle(DutyScheduleService $scheduleService, NotificationService $notificationService): int
    {
        $rawDate = $this->option('date') ?: now()->toDateString();
        $force = (bool) $this->option('force');

        try {
            $date = Carbon::parse($rawDate);
        } catch (\Exception $e) {
            $this->error("Format tanggal tidak valid: {$rawDate}");
            return Command::FAILURE;
        }

        $dateString = $date->toDateString();
        $this->info("Menyiapkan pengingat piket PST untuk tanggal: {$dateString} ({$date->translatedFormat('l')})");

        // Periksa hari kerja (Senin s.d. Jumat)
        if ($date->isWeekend() && ! $force) {
            $this->comment("Tanggal {$dateString} adalah akhir pekan (Sabtu/Minggu). Pengingat piket dilewati. Gunakan opsi --force untuk memaksa.");
            return Command::SUCCESS;
        }

        // Ambil petugas piket efektif (jadwal asli + swap)
        $effectiveOfficers = $scheduleService->getEffectiveOfficersForDate($dateString);

        if ($effectiveOfficers->isEmpty()) {
            $this->warn("Tidak ada petugas yang terjadwal piket pada tanggal {$dateString}.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$effectiveOfficers->count()} petugas piket efektif:");

        $sentCount = 0;
        foreach ($effectiveOfficers as $officer) {
            $this->line(" - Mengirim pengingat ke: {$officer->name} ({$officer->username})");
            $log = $notificationService->sendDutyReminder($officer, $date);

            if ($log && $log->status === 'sent') {
                $sentCount++;
            }
        }

        $this->info("Selesai. {$sentCount} dari {$effectiveOfficers->count()} pengingat piket berhasil diproses.");

        return Command::SUCCESS;
    }
}
