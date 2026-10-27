<?php

namespace App\Services;

use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class QueueNumberService
{
    /**
     * Generate nomor antrean harian berikutnya untuk tamu kios.
     *
     * Format: PST-01, PST-02, ... PST-99, PST-100, ...
     * Reset otomatis per tanggal (berdasarkan visit_date).
     * Aman dari duplikat saat input bersamaan (DB lock + unique constraint).
     *
     * @param  string  $visitDate  Format Y-m-d
     * @return array{queue_seq: int, queue_number: string}
     */
    public function generateNext(string $visitDate): array
    {
        return DB::transaction(function () use ($visitDate) {
            // Lock baris terakhir untuk tanggal ini agar tidak ada race condition
            $lastSeq = Visit::whereDate('visit_date', $visitDate)
                ->where('channel', 'kiosk')
                ->whereNotNull('queue_seq')
                ->lockForUpdate()
                ->max('queue_seq');

            $nextSeq = ($lastSeq ?? 0) + 1;

            // Format: PST-01 s.d. PST-99, lalu PST-100 dst.
            $padLength = $nextSeq > 99 ? strlen((string) $nextSeq) : 2;
            $queueNumber = 'PST-' . str_pad($nextSeq, $padLength, '0', STR_PAD_LEFT);

            return [
                'queue_seq' => $nextSeq,
                'queue_number' => $queueNumber,
            ];
        });
    }
}
