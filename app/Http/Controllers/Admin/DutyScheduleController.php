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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Unduh template file CSV untuk impor jadwal piket.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'template_jadwal_piket_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header kolom
            fputcsv($handle, ['tanggal', 'username', 'catatan_opsional']);

            // Baris contoh
            $today = now()->startOfWeek();
            fputcsv($handle, [$today->toDateString(), 'petugas1', 'Senin - Petugas 1']);
            fputcsv($handle, [$today->copy()->addDay()->toDateString(), 'petugas2', 'Selasa - Petugas 2']);
            fputcsv($handle, [$today->copy()->addDays(2)->toDateString(), 'petugas3', 'Rabu - Petugas 3']);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Impor jadwal piket massal dari file CSV.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'overwrite' => 'nullable|boolean',
        ], [
            'csv_file.required' => 'Pilih file CSV yang akan diimpor.',
            'csv_file.mimes' => 'Format file harus berupa CSV (.csv atau .txt).',
            'csv_file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('csv_file');
        $overwrite = (bool) $request->input('overwrite', false);

        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        // Abaikan BOM jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 1000, ',');
        if (! $header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        // Petakan header kolom
        $normalizedHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $dateIdx = array_search('tanggal', $normalizedHeader, true);
        $usernameIdx = array_search('username', $normalizedHeader, true);

        // Jika tidak ada header bertuliskan 'tanggal' atau 'username', asumsikan kolom 0 tanggal, kolom 1 username
        if ($dateIdx === false) {
            $dateIdx = 0;
        }
        if ($usernameIdx === false) {
            $usernameIdx = 1;
        }

        // Cache petugas aktif untuk lookup cepat
        $officers = User::whereIn('role', ['petugas', 'admin'])
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($u) => strtolower($u->username));

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) {
                    continue; // baris kosong
                }

                $rawDate = trim($row[$dateIdx] ?? '');
                $rawUsername = strtolower(trim($row[$usernameIdx] ?? ''));

                if (empty($rawDate) || empty($rawUsername)) {
                    $skipped++;
                    continue;
                }

                // Parse tanggal
                try {
                    $dutyDate = Carbon::parse($rawDate)->toDateString();
                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNum}: format tanggal '{$rawDate}' tidak valid.";
                    $skipped++;
                    continue;
                }

                // Cari petugas
                $officer = $officers->get($rawUsername);
                if (! $officer) {
                    // Coba cari case-insensitive jika belum ketemu
                    $officer = User::whereIn('role', ['petugas', 'admin'])
                        ->where('username', $rawUsername)
                        ->first();
                }

                if (! $officer) {
                    $errors[] = "Baris {$rowNum}: Petugas dengan username '{$rawUsername}' tidak ditemukan.";
                    $skipped++;
                    continue;
                }

                $existing = DutySchedule::whereDate('duty_date', $dutyDate)->first();

                if ($existing) {
                    if ($overwrite) {
                        $existing->update([
                            'officer_id' => $officer->id,
                            'created_by' => Auth::id(),
                        ]);
                        $imported++;
                    } else {
                        // Jika sudah ada dan tidak overwrite, lewati
                        $skipped++;
                    }
                } else {
                    DutySchedule::create([
                        'duty_date' => $dutyDate,
                        'officer_id' => $officer->id,
                        'created_by' => Auth::id(),
                    ]);
                    $imported++;
                }
            }

            DB::commit();
            fclose($handle);
        } catch (\Throwable $th) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Terjadi kesalahan saat memproses impor CSV: ' . $th->getMessage());
        }

        $msg = "Berhasil mengimpor {$imported} jadwal piket.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} baris dilewati / tidak diproses).";
        }
        if (! empty($errors)) {
            $msg .= ' Catatan: ' . implode('; ', array_slice($errors, 0, 3));
        }

        return back()->with('success', $msg);
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
