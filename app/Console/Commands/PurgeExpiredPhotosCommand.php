<?php

namespace App\Console\Commands;

use App\Models\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredPhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:purge-expired
                            {--dry-run : Hanya tampilkan jumlah file yang akan dihapus tanpa menghapus}
                            {--days=365 : Jumlah hari retensi foto (default: 365 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus file foto kunjungan yang lebih dari 365 hari. Data teks kunjungan tetap tersimpan (sesuai UU PDP & PRD).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        $cutoffDate = now()->subDays($days)->toDateString();

        $this->info("Mencari foto kunjungan lebih dari {$days} hari (sebelum {$cutoffDate})...");

        // Ambil visit yang punya foto dan sudah lewat masa retensi
        $expiredVisits = Visit::whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->whereDate('visit_date', '<', $cutoffDate)
            ->get();

        $totalFound = $expiredVisits->count();

        if ($totalFound === 0) {
            $this->info('Tidak ada foto yang sudah melewati masa retensi.');

            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN] Ditemukan {$totalFound} foto yang akan dihapus:");
            foreach ($expiredVisits as $visit) {
                $this->line("  - Tiket: {$visit->ticket_code} | Tanggal: {$visit->visit_date} | Path: {$visit->photo_path}");
            }
            $this->warn("[DRY RUN] Tidak ada file yang dihapus. Jalankan tanpa --dry-run untuk menghapus.");

            return self::SUCCESS;
        }

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($expiredVisits as $visit) {
            try {
                // Hapus file fisik dari storage
                if (Storage::disk('local')->exists($visit->photo_path)) {
                    Storage::disk('local')->delete($visit->photo_path);
                    $deletedCount++;
                } else {
                    // File sudah tidak ada, tetap bersihkan path-nya
                    $deletedCount++;
                }

                // Set photo_path ke null (data teks tetap tersimpan sesuai PRD)
                $visit->update(['photo_path' => null]);
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("Gagal menghapus foto tiket {$visit->ticket_code}: {$e->getMessage()}");
                Log::error("photos:purge-expired gagal untuk visit #{$visit->id}", [
                    'ticket_code' => $visit->ticket_code,
                    'photo_path' => $visit->photo_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai. Foto dihapus: {$deletedCount}, Gagal: {$failedCount}.");
        Log::info("photos:purge-expired selesai — dihapus: {$deletedCount}, gagal: {$failedCount}, retensi: {$days} hari.");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
