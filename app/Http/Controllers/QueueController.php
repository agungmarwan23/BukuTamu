<?php

namespace App\Http\Controllers;

use App\Models\CallEvent;
use App\Models\Visit;
use App\Models\VisitStatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QueueController extends Controller
{
    /**
     * Halaman resepsionis: daftar antrean kios hari ini.
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $visits = Visit::whereDate('visit_date', $today)
            ->where('channel', 'kiosk')
            ->orderBy('queue_seq')
            ->get();

        $stats = [
            'total' => $visits->count(),
            'menunggu' => $visits->where('status', 'menunggu')->count(),
            'dipanggil' => $visits->where('status', 'dipanggil')->count(),
            'dilayani' => $visits->where('status', 'dilayani')->count(),
            'selesai' => $visits->where('status', 'selesai')->count(),
            'tidak_hadir' => $visits->where('status', 'tidak_hadir')->count(),
        ];

        // Panggilan terakhir (untuk highlight)
        $lastCall = CallEvent::whereHas('visit', function ($q) use ($today) {
            $q->whereDate('visit_date', $today)->where('channel', 'kiosk');
        })->latest('created_at')->first();

        return view('resepsionis.dashboard', compact('visits', 'stats', 'lastCall'));
    }

    /**
     * Panggil nomor antrean: ubah status menunggu → dipanggil.
     */
    public function call(Request $request, Visit $visit): RedirectResponse
    {
        $request->validate([
            'counter_label' => 'required|string|max:50',
        ]);

        // Hanya bisa memanggil tiket kios yang masih menunggu
        if ($visit->channel !== 'kiosk') {
            return back()->with('error', 'Hanya tiket kios yang bisa dipanggil.');
        }

        if (! in_array($visit->status, ['menunggu'])) {
            return back()->with('error', 'Tiket ini tidak dalam status menunggu.');
        }

        // Update status → dipanggil
        $visit->update([
            'status' => 'dipanggil',
            'called_at' => now(),
        ]);

        // Catat log transisi status
        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'from_status' => 'menunggu',
            'to_status' => 'dipanggil',
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        // Catat call event
        CallEvent::create([
            'visit_id' => $visit->id,
            'called_by' => Auth::id(),
            'counter_label' => $request->input('counter_label', 'Loket 1'),
            'created_at' => now(),
        ]);

        return back()->with('success', "Nomor {$visit->queue_number} berhasil dipanggil.");
    }

    /**
     * Ulang panggilan tanpa mengubah status.
     */
    public function recall(Request $request, Visit $visit): RedirectResponse
    {
        $request->validate([
            'counter_label' => 'required|string|max:50',
        ]);

        if ($visit->channel !== 'kiosk') {
            return back()->with('error', 'Hanya tiket kios yang bisa dipanggil.');
        }

        if ($visit->status !== 'dipanggil') {
            return back()->with('error', 'Hanya tiket berstatus "Dipanggil" yang bisa diulang.');
        }

        // Catat call event baru (tanpa ubah status)
        CallEvent::create([
            'visit_id' => $visit->id,
            'called_by' => Auth::id(),
            'counter_label' => $request->input('counter_label', 'Loket 1'),
            'created_at' => now(),
        ]);

        return back()->with('success', "Panggilan ulang {$visit->queue_number}.");
    }

    /**
     * Lewati tamu: ubah status → tidak_hadir.
     */
    public function skip(Visit $visit): RedirectResponse
    {
        if ($visit->channel !== 'kiosk') {
            return back()->with('error', 'Hanya tiket kios yang bisa dilewati.');
        }

        if (! in_array($visit->status, ['dipanggil'])) {
            return back()->with('error', 'Hanya tiket berstatus "Dipanggil" yang bisa dilewati.');
        }

        $oldStatus = $visit->status;

        $visit->update([
            'status' => 'tidak_hadir',
        ]);

        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'from_status' => $oldStatus,
            'to_status' => 'tidak_hadir',
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        return back()->with('success', "Nomor {$visit->queue_number} dilewati (Tidak Hadir).");
    }

    /**
     * API endpoint publik untuk layar TV antrean.
     * Mengembalikan: nomor sedang dipanggil, 5 panggilan terakhir, jumlah menunggu.
     * TIDAK menampilkan nama atau data pribadi tamu.
     */
    public function screenApi(): JsonResponse
    {
        $today = now()->toDateString();

        // Tiket yang sedang dipanggil (paling terakhir dipanggil)
        $currentCall = CallEvent::whereHas('visit', function ($q) use ($today) {
            $q->whereDate('visit_date', $today)
              ->where('channel', 'kiosk')
              ->where('status', 'dipanggil');
        })
        ->with(['visit:id,queue_number,purpose,service_type,non_pst_type'])
        ->latest('created_at')
        ->first();

        $currentData = null;
        if ($currentCall) {
            $currentData = [
                'call_event_id' => $currentCall->id,
                'queue_number' => $currentCall->visit->queue_number,
                'counter_label' => $currentCall->counter_label,
                'purpose' => $currentCall->visit->purpose,
                'called_at' => $currentCall->created_at->format('H:i:s'),
            ];
        }

        // 5 panggilan terakhir (semua status, terurut terbaru)
        $recentCalls = CallEvent::whereHas('visit', function ($q) use ($today) {
            $q->whereDate('visit_date', $today)->where('channel', 'kiosk');
        })
        ->with(['visit:id,queue_number,status'])
        ->latest('created_at')
        ->take(6) // ambil 6 lalu skip yang pertama (current)
        ->get()
        ->map(function ($call) {
            return [
                'call_event_id' => $call->id,
                'queue_number' => $call->visit->queue_number,
                'counter_label' => $call->counter_label,
                'status' => $call->visit->status,
                'called_at' => $call->created_at->format('H:i:s'),
            ];
        });

        // Jika ada current, hilangkan dari recent calls agar tidak duplikat
        if ($currentCall) {
            $recentCalls = $recentCalls->filter(fn ($c) => $c['call_event_id'] !== $currentCall->id);
        }
        $recentCalls = $recentCalls->take(5)->values();

        // Jumlah menunggu
        $waiting = Visit::whereDate('visit_date', $today)
            ->where('channel', 'kiosk')
            ->where('status', 'menunggu')
            ->count();

        return response()->json([
            'current' => $currentData,
            'recent' => $recentCalls,
            'waiting_count' => $waiting,
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Halaman layar TV publik read-only.
     */
    public function screen(): View
    {
        return view('layar.index');
    }

    /**
     * API data antrean untuk polling di halaman resepsionis.
     */
    public function queueData(): JsonResponse
    {
        $today = now()->toDateString();

        $visits = Visit::whereDate('visit_date', $today)
            ->where('channel', 'kiosk')
            ->orderBy('queue_seq')
            ->get(['id', 'queue_seq', 'queue_number', 'full_name', 'institution', 'purpose', 'service_type', 'non_pst_type', 'status', 'called_at', 'created_at']);

        $stats = [
            'total' => $visits->count(),
            'menunggu' => $visits->where('status', 'menunggu')->count(),
            'dipanggil' => $visits->where('status', 'dipanggil')->count(),
            'dilayani' => $visits->where('status', 'dilayani')->count(),
            'selesai' => $visits->where('status', 'selesai')->count(),
            'tidak_hadir' => $visits->where('status', 'tidak_hadir')->count(),
        ];

        $lastCall = CallEvent::whereHas('visit', function ($q) use ($today) {
            $q->whereDate('visit_date', $today)->where('channel', 'kiosk');
        })->latest('created_at')->first();

        return response()->json([
            'visits' => $visits,
            'stats' => $stats,
            'last_call_id' => $lastCall?->id,
        ]);
    }
}
