<?php

namespace App\Http\Controllers;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitStatusLog;
use App\Services\DutyScheduleService;
use App\Services\Notification\NotificationService;
use App\Services\OfficerAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PetugasController extends Controller
{
    public function __construct(
        protected DutyScheduleService $dutyScheduleService,
        protected OfficerAssignmentService $assignmentService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Halaman portal petugas (FR-20 sampai FR-24).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'antrean');
        $today = now()->toDateString();

        // 1. Tab Antrean Saya (Kios / Offline)
        $antreanPreset = $request->query('antrean_preset');
        $antreanDate = $request->query('antrean_date');
        $antreanStartDate = $request->query('antrean_start_date');
        $antreanEndDate = $request->query('antrean_end_date');
        $antreanStatus = $request->query('antrean_status');

        $antreanQuery = Visit::where('channel', 'kiosk')
            ->with(['attachments', 'assignedOfficer'])
            ->orderByDesc('visit_date')
            ->orderBy('queue_seq');

        if (! $user->isAdmin()) {
            $antreanQuery->where(function ($q) use ($user) {
                $q->where('assigned_officer_id', $user->id)
                  ->orWhereNull('assigned_officer_id');
            });
        }

        // Filter tanggal
        if ($antreanStartDate && $antreanEndDate) {
            $antreanQuery->whereBetween('visit_date', [$antreanStartDate, $antreanEndDate]);
            $antreanPreset = 'custom';
        } elseif ($antreanDate) {
            $antreanQuery->whereDate('visit_date', $antreanDate);
            $antreanPreset = 'custom';
        } elseif ($antreanPreset === 'all') {
            // Tampilkan semua tanggal tanpa filter
        } elseif ($antreanPreset === 'yesterday') {
            $antreanQuery->whereDate('visit_date', now()->subDay()->toDateString());
        } elseif ($antreanPreset === 'this_month') {
            $antreanQuery->whereBetween('visit_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
        } else {
            // Default: hari ini
            $antreanPreset = 'today';
            $antreanQuery->whereDate('visit_date', $today);
        }

        // Filter status
        if ($antreanStatus) {
            $antreanQuery->where('status', $antreanStatus);
        }

        $myVisits = $antreanQuery->get();

        $stats = [
            'total' => $myVisits->count(),
            'menunggu' => $myVisits->where('status', 'menunggu')->count(),
            'dipanggil' => $myVisits->where('status', 'dipanggil')->count(),
            'dilayani' => $myVisits->where('status', 'dilayani')->count(),
            'selesai' => $myVisits->where('status', 'selesai')->count(),
            'tidak_hadir' => $myVisits->where('status', 'tidak_hadir')->count(),
        ];

        // 2. Tab Permintaan Online
        $onlineDate = $request->query('online_date');
        $onlineStartDate = $request->query('online_start_date');
        $onlineEndDate = $request->query('online_end_date');
        $onlineStatus = $request->query('online_status');
        $onlineSearch = $request->query('online_search');

        $onlineQuery = Visit::where('channel', 'online')
            ->with(['attachments', 'assignedOfficer'])
            ->latest('created_at');

        if (! $user->isAdmin()) {
            $onlineQuery->where(function ($q) use ($user) {
                $q->where('assigned_officer_id', $user->id)
                  ->orWhereNull('assigned_officer_id');
            });
        }

        if ($onlineStartDate && $onlineEndDate) {
            $onlineQuery->whereBetween('visit_date', [$onlineStartDate, $onlineEndDate]);
        } elseif ($onlineDate) {
            $onlineQuery->whereDate('visit_date', $onlineDate);
        }

        if ($onlineStatus) {
            $onlineQuery->where('status', $onlineStatus);
        }

        if ($onlineSearch) {
            $search = '%' . trim($onlineSearch) . '%';
            $onlineQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('ticket_code', 'like', $search)
                  ->orWhere('institution', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        $onlineVisits = $onlineQuery->paginate(15, ['*'], 'online_page')->withQueryString();

        // 3. Tab Jadwal Saya
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->addMonth()->endOfMonth()->toDateString();
        $mySchedule = $this->dutyScheduleService->getScheduleForOfficer($user->id, $startDate, $endDate);

        // 4. Tab Swap Piket
        // Tanggal piket mendatang milik petugas ini untuk dropdown form swap
        $upcomingDutyDates = DutySchedule::where('officer_id', $user->id)
            ->whereDate('duty_date', '>=', $today)
            ->orderBy('duty_date')
            ->get();

        // Daftar rekan petugas untuk pilihan pengganti
        $availableOfficers = User::where('role', 'petugas')
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Riwayat swap yang melibatkan petugas ini
        $swapHistory = DutySwap::where(function ($q) use ($user) {
            $q->where('original_officer_id', $user->id)
              ->orWhere('replacement_officer_id', $user->id);
        })
        ->with(['originalOfficer', 'replacementOfficer', 'swappedBy'])
        ->orderByDesc('duty_date')
        ->orderByDesc('id')
        ->get();

        return view('petugas.index', compact(
            'tab',
            'myVisits',
            'stats',
            'onlineVisits',
            'mySchedule',
            'upcomingDutyDates',
            'availableOfficers',
            'swapHistory',
            'today',
            'antreanPreset',
            'antreanDate',
            'antreanStartDate',
            'antreanEndDate',
            'antreanStatus',
            'onlineDate',
            'onlineStartDate',
            'onlineEndDate',
            'onlineStatus',
            'onlineSearch'
        ));
    }

    /**
     * Petugas memulai layanan tamu: status → dilayani.
     */
    public function startService(Visit $visit): RedirectResponse
    {
        $user = Auth::user();

        // Validasi otorisasi penugasan
        if (! $user->isAdmin() && $visit->assigned_officer_id !== null && $visit->assigned_officer_id !== $user->id) {
            abort(403, 'Anda tidak ditugaskan untuk melayani tiket antrean ini.');
        }

        if (! in_array($visit->status, ['menunggu', 'dipanggil'])) {
            return back()->with('error', "Tiket ini tidak bisa dimulai karena berstatus {$visit->status}.");
        }

        $oldStatus = $visit->status;

        $visit->update([
            'status' => 'dilayani',
            'assigned_officer_id' => $user->id,
            'started_at' => now(),
        ]);

        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'from_status' => $oldStatus,
            'to_status' => 'dilayani',
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', "Layanan untuk nomor antrean {$visit->queue_number} resmi dimulai.");
    }

    /**
     * Petugas menyelesaikan layanan tamu: status → selesai + simpan / edit catatan layanan.
     */
    public function finishService(Request $request, Visit $visit): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $visit->assigned_officer_id !== null && $visit->assigned_officer_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak untuk memproses tiket ini.');
        }

        if (! in_array($visit->status, ['dilayani', 'selesai'])) {
            return back()->with('error', 'Hanya tiket berstatus "Sedang Dilayani" atau "Selesai" yang dapat diproses.');
        }

        $request->validate([
            'officer_note' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $visit->status;
        $isAlreadyFinished = $oldStatus === 'selesai';

        $visit->update([
            'status' => 'selesai',
            'assigned_officer_id' => $visit->assigned_officer_id ?: $user->id,
            'finished_at' => $visit->finished_at ?: now(),
            'officer_note' => $request->input('officer_note'),
        ]);

        if (! $isAlreadyFinished) {
            VisitStatusLog::create([
                'visit_id' => $visit->id,
                'from_status' => $oldStatus,
                'to_status' => 'selesai',
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            // Kirim survei SKM otomatis jika URL SKM sudah diatur (FR-41)
            $this->notificationService->sendSkmSurvey($visit->fresh());

            return back()->with('success', "Layanan untuk nomor {$visit->queue_number} berhasil diselesaikan.");
        }

        return back()->with('success', "Catatan layanan untuk nomor {$visit->queue_number} berhasil diperbarui.");
    }

    /**
     * Ubah status antrean secara langsung (misal: Batalkan atau Tidak Hadir).
     */
    public function updateStatus(Request $request, Visit $visit): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'status' => 'required|in:tidak_hadir,dibatalkan',
            'officer_note' => 'nullable|string|max:500',
        ]);

        $targetStatus = $request->input('status');
        $oldStatus = $visit->status;

        // Validasi transisi
        if ($targetStatus === 'tidak_hadir' && ! in_array($oldStatus, ['menunggu', 'dipanggil'])) {
            return back()->with('error', 'Tiket hanya bisa ditandai Tidak Hadir jika belum dilayani.');
        }

        if ($targetStatus === 'dibatalkan' && in_array($oldStatus, ['selesai'])) {
            return back()->with('error', 'Tiket yang sudah selesai tidak dapat dibatalkan.');
        }

        $visit->update([
            'status' => $targetStatus,
            'officer_note' => $request->input('officer_note') ?: $visit->officer_note,
        ]);

        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'from_status' => $oldStatus,
            'to_status' => $targetStatus,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', "Status tiket berhasil diubah menjadi {$targetStatus}.");
    }

    /**
     * Tindak lanjuti permohonan tiket online.
     */
    public function updateOnlineStatus(Request $request, Visit $visit): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'status' => 'required|in:dilayani,selesai,dibatalkan',
            'officer_note' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $visit->status;
        $targetStatus = $request->input('status');

        $updates = [
            'status' => $targetStatus,
            'officer_note' => $request->input('officer_note'),
            'assigned_officer_id' => $visit->assigned_officer_id ?: $user->id,
        ];

        if ($targetStatus === 'dilayani' && ! $visit->started_at) {
            $updates['started_at'] = now();
        }

        if ($targetStatus === 'selesai' && ! $visit->finished_at) {
            $updates['finished_at'] = now();
        }

        $visit->update($updates);

        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'from_status' => $oldStatus,
            'to_status' => $targetStatus,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Kirim survei SKM otomatis jika status menjadi selesai (FR-41)
        if ($targetStatus === 'selesai') {
            $this->notificationService->sendSkmSurvey($visit->fresh());
        }

        return redirect()->route('petugas.index', ['tab' => 'online'])
            ->with('success', "Status permohonan online {$visit->ticket_code} berhasil diperbarui.");
    }

    /**
     * Ajukan swap (tukar) petugas piket.
     * PENTING: Jadwal asli di duty_schedules TIDAK PERNAH DIUBAH / DITIMPA.
     */
    public function storeSwap(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'duty_date' => 'required|date',
            'replacement_officer_id' => 'required|exists:users,id|different:' . $user->id,
            'reason' => 'required|string|min:5|max:500',
        ], [
            'duty_date.required' => 'Pilih tanggal piket yang ingin ditukar.',
            'replacement_officer_id.required' => 'Pilih petugas pengganti.',
            'replacement_officer_id.different' => 'Petugas pengganti tidak boleh diri sendiri.',
            'reason.required' => 'Wajib mengisi alasan pertukaran jadwal.',
            'reason.min' => 'Alasan pertukaran minimal 5 karakter.',
        ]);

        $dutyDate = $request->input('duty_date');

        // Pastikan petugas memiliki jadwal asli pada tanggal tersebut (atau user adalah admin)
        $hasSchedule = DutySchedule::where('officer_id', $user->id)
            ->whereDate('duty_date', $dutyDate)
            ->exists();

        if (! $hasSchedule && ! $user->isAdmin()) {
            return back()->withErrors([
                'duty_date' => 'Anda tidak memiliki jadwal piket asli pada tanggal yang dipilih.',
            ])->withInput();
        }

        // Simpan swap ke tabel duty_swaps (jadwal asli duty_schedules tetap utuh!)
        DutySwap::create([
            'duty_date' => $dutyDate,
            'original_officer_id' => $user->id,
            'replacement_officer_id' => $request->input('replacement_officer_id'),
            'reason' => $request->input('reason'),
            'swapped_by' => $user->id,
            'created_at' => now(),
        ]);

        return redirect()->route('petugas.index', ['tab' => 'swap'])
            ->with('success', 'Pertukaran jadwal piket berhasil disimpan. Jadwal efektif otomatis diperbarui tanpa mengubah jadwal asli.');
    }
}
