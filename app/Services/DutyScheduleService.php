<?php

namespace App\Services;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\User;
use Illuminate\Support\Collection;

class DutyScheduleService
{
    /**
     * Dapatkan daftar User petugas piket efektif untuk tanggal tertentu,
     * memperhitungkan jadwal asli di duty_schedules dan riwayat penggantian di duty_swaps.
     *
     * @param string $date Format Y-m-d
     * @return Collection<int, User>
     */
    public function getEffectiveOfficersForDate(string $date): Collection
    {
        // 1. Ambil jadwal asli
        $originalOfficerIds = DutySchedule::whereDate('duty_date', $date)
            ->pluck('officer_id')
            ->toArray();

        // 2. Ambil semua swap pada tanggal ini urut waktu/id
        $swaps = DutySwap::whereDate('duty_date', $date)
            ->orderBy('id')
            ->get();

        $activeIds = $originalOfficerIds;

        foreach ($swaps as $swap) {
            // Hilangkan original officer jika ada di daftar aktif
            $key = array_search($swap->original_officer_id, $activeIds, true);
            if ($key !== false) {
                unset($activeIds[$key]);
            }

            // Tambahkan replacement officer
            if (! in_array($swap->replacement_officer_id, $activeIds, true)) {
                $activeIds[] = $swap->replacement_officer_id;
            }
        }

        $activeIds = array_values(array_unique($activeIds));

        if (empty($activeIds)) {
            return collect();
        }

        return User::whereIn('id', $activeIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Cek apakah petugas tertentu bertugas pada tanggal tertentu (setelah swap).
     */
    public function isOfficerOnDuty(int $officerId, string $date): bool
    {
        $officers = $this->getEffectiveOfficersForDate($date);

        return $officers->contains('id', $officerId);
    }

    /**
     * Dapatkan jadwal lengkap untuk seorang petugas dalam rentang waktu tertentu,
     * lengkap dengan status apakah jadwal normal, digantikan, atau menggantikan.
     *
     * @return Collection<int, array>
     */
    public function getScheduleForOfficer(int $officerId, string $startDate, string $endDate): Collection
    {
        // Jadwal asli petugas ini
        $originalSchedules = DutySchedule::where('officer_id', $officerId)
            ->whereDate('duty_date', '>=', $startDate)
            ->whereDate('duty_date', '<=', $endDate)
            ->orderBy('duty_date')
            ->get();

        // Swap keluar (petugas ini digantikan orang lain)
        $swapsOut = DutySwap::where('original_officer_id', $officerId)
            ->whereDate('duty_date', '>=', $startDate)
            ->whereDate('duty_date', '<=', $endDate)
            ->with(['replacementOfficer', 'swappedBy'])
            ->get()
            ->keyBy(fn ($s) => $s->duty_date->format('Y-m-d'));

        // Swap masuk (petugas ini menggantikan orang lain)
        $swapsIn = DutySwap::where('replacement_officer_id', $officerId)
            ->whereDate('duty_date', '>=', $startDate)
            ->whereDate('duty_date', '<=', $endDate)
            ->with(['originalOfficer', 'swappedBy'])
            ->get();

        $results = collect();

        // 1. Proses jadwal asli
        foreach ($originalSchedules as $schedule) {
            $dateStr = $schedule->duty_date->format('Y-m-d');
            $swap = $swapsOut->get($dateStr);

            if ($swap) {
                $results->push([
                    'duty_date' => $schedule->duty_date,
                    'type' => 'swapped_out',
                    'status_label' => 'Digantikan',
                    'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'partner_name' => $swap->replacementOfficer?->name ?? 'Petugas Pengganti',
                    'reason' => $swap->reason,
                    'is_active_duty' => false,
                    'created_at' => $swap->created_at,
                ]);
            } else {
                $results->push([
                    'duty_date' => $schedule->duty_date,
                    'type' => 'regular',
                    'status_label' => 'Jadwal Normal',
                    'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'partner_name' => null,
                    'reason' => null,
                    'is_active_duty' => true,
                    'created_at' => $schedule->created_at,
                ]);
            }
        }

        // 2. Proses swap masuk (menggantikan)
        foreach ($swapsIn as $swap) {
            $results->push([
                'duty_date' => $swap->duty_date,
                'type' => 'swapped_in',
                'status_label' => 'Menggantikan',
                'badge_class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'partner_name' => $swap->originalOfficer?->name ?? 'Petugas Asli',
                'reason' => $swap->reason,
                'is_active_duty' => true,
                'created_at' => $swap->created_at,
            ]);
        }

        return $results->sortBy('duty_date')->values();
    }
}
