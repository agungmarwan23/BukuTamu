<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DutySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DutyScheduleController extends Controller
{
    /**
     * Tampilan daftar jadwal piket bulanan (FR-32).
     */
    public function index(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Ambil jadwal dalam bulan ini
        $schedules = DutySchedule::whereDate('duty_date', '>=', $startDate->toDateString())
            ->whereDate('duty_date', '<=', $endDate->toDateString())
            ->with(['officer', 'creator'])
            ->orderBy('duty_date')
            ->get()
            ->groupBy(fn ($item) => $item->duty_date->format('Y-m-d'));

        // Daftar petugas yang dapat dipiketkan
        $officers = User::whereIn('role', ['petugas', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.schedules.index', compact(
            'schedules',
            'officers',
            'month',
            'year',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Tambah penugasan piket per tanggal (harian).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'duty_date' => 'required|date',
            'officer_id' => 'required|exists:users,id',
        ], [
            'duty_date.required' => 'Pilih tanggal piket.',
            'officer_id.required' => 'Pilih petugas piket.',
        ]);

        $dutyDate = $request->input('duty_date');
        $officerId = $request->input('officer_id');

        // Cek apakah sudah pernah didaftarkan
        $exists = DutySchedule::whereDate('duty_date', $dutyDate)
            ->where('officer_id', $officerId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Petugas ini sudah memiliki jadwal piket pada tanggal tersebut.')->withInput();
        }

        DutySchedule::create([
            'duty_date' => $dutyDate,
            'officer_id' => $officerId,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Jadwal piket berhasil ditambahkan.');
    }

    /**
     * Penugasan massal jadwal piket bulanan secara rotasi / bergilir (FR-32).
     */
    public function massAssign(Request $request): RedirectResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2035',
            'officer_ids' => 'required|array|min:1',
            'officer_ids.*' => 'exists:users,id',
        ], [
            'officer_ids.required' => 'Pilih minimal satu petugas untuk dirotasi.',
            'officer_ids.min' => 'Pilih minimal satu petugas untuk dirotasi.',
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $officerIds = array_values($request->input('officer_ids'));
        $overwrite = (bool) $request->input('overwrite', false);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $officerCount = count($officerIds);
        $officerIndex = 0;
        $assignedDaysCount = 0;

        DB::transaction(function () use ($startDate, $daysInMonth, $officerIds, $officerCount, &$officerIndex, &$assignedDaysCount, $overwrite) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = $startDate->copy()->day($day);

                // Lewati hari Sabtu dan Minggu (hari kerja BPS: Senin s.d. Jumat)
                if ($currentDate->isWeekend()) {
                    continue;
                }

                $dateString = $currentDate->toDateString();

                if ($overwrite) {
                    DutySchedule::whereDate('duty_date', $dateString)->delete();
                } else {
                    // Jika sudah ada dan tidak overwrite, lewati
                    if (DutySchedule::whereDate('duty_date', $dateString)->exists()) {
                        continue;
                    }
                }

                // Ambil petugas giliran
                $assignedOfficerId = $officerIds[$officerIndex % $officerCount];
                $officerIndex++;

                DutySchedule::create([
                    'duty_date' => $dateString,
                    'officer_id' => $assignedOfficerId,
                    'created_by' => Auth::id(),
                ]);

                $assignedDaysCount++;
            }
        });

        return back()->with('success', "Penugasan massal berhasil diterapkan untuk {$assignedDaysCount} hari kerja.");
    }

    /**
     * Hapus satu baris jadwal piket.
     */
    public function destroy(DutySchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Jadwal piket berhasil dihapus.');
    }
}
