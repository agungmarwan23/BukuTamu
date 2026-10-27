<?php

namespace App\Services;

use App\Models\User;
use App\Models\Visit;
use App\Models\VisitStatusLog;
use Illuminate\Support\Facades\Auth;

class OfficerAssignmentService
{
    public function __construct(
        protected DutyScheduleService $dutyScheduleService
    ) {}

    /**
     * Cari petugas piket yang tersedia dengan beban tiket aktif terendah pada tanggal kunjungan.
     * Tiket aktif adalah yang berstatus 'menunggu', 'dipanggil', atau 'dilayani'.
     */
    public function findBestOfficerForDate(string $visitDate): ?User
    {
        $officers = $this->dutyScheduleService->getEffectiveOfficersForDate($visitDate);

        if ($officers->isEmpty()) {
            return null;
        }

        // Jika hanya 1 petugas piket
        if ($officers->count() === 1) {
            return $officers->first();
        }

        $officerIds = $officers->pluck('id')->toArray();

        // Hitung beban tiket aktif per petugas untuk tanggal ini
        $activeWorkloads = Visit::whereDate('visit_date', $visitDate)
            ->whereIn('assigned_officer_id', $officerIds)
            ->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])
            ->selectRaw('assigned_officer_id, count(*) as count')
            ->groupBy('assigned_officer_id')
            ->pluck('count', 'assigned_officer_id')
            ->toArray();

        // Cari officer dengan count paling kecil (default 0 jika belum ada tiket)
        $bestOfficer = $officers->sortBy(function ($officer) use ($activeWorkloads) {
            return $activeWorkloads[$officer->id] ?? 0;
        })->first();

        return $bestOfficer;
    }

    /**
     * Tugaskan tiket ke petugas piket terbaik secara otomatis.
     */
    public function assignNextAvailableOfficer(Visit $visit): ?User
    {
        $visitDate = $visit->visit_date instanceof \DateTimeInterface
            ? $visit->visit_date->format('Y-m-d')
            : (string) $visit->visit_date;

        $officer = $this->findBestOfficerForDate($visitDate);

        if ($officer) {
            $visit->update([
                'assigned_officer_id' => $officer->id,
            ]);
        }

        return $officer;
    }

    /**
     * Resepsionis/Admin mengalihkan / menugaskan tiket ke petugas lain secara manual.
     */
    public function reassignOfficer(Visit $visit, int $newOfficerId, ?int $reassignedByUserId = null): Visit
    {
        $oldOfficerId = $visit->assigned_officer_id;

        $visit->update([
            'assigned_officer_id' => $newOfficerId,
        ]);

        return $visit;
    }
}
