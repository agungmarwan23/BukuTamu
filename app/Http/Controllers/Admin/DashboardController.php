<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\User;
use App\Models\Visit;
use App\Services\DutyScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DutyScheduleService $dutyScheduleService
    ) {}

    public function index(): View
    {
        $today = now()->toDateString();
        $startOfWeek = now()->startOfWeek()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        // 1. Ringkasan Angka Utama
        $todayCount = Visit::whereDate('visit_date', $today)->count();
        $weekCount = Visit::whereDate('visit_date', '>=', $startOfWeek)->count();
        $monthCount = Visit::whereDate('visit_date', '>=', $startOfMonth)->count();

        // Tiket belum selesai (aktif)
        $activeCount = Visit::whereDate('visit_date', $today)
            ->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])
            ->count();

        // 2. Rata-rata Waktu Tunggu & Waktu Layanan (dalam menit)
        // Hitung untuk tiket selesai hari ini atau bulan ini
        $finishedVisits = Visit::whereDate('visit_date', '>=', $startOfMonth)
            ->where('status', 'selesai')
            ->whereNotNull('started_at')
            ->get();

        $totalWaitMinutes = 0;
        $totalServiceMinutes = 0;
        $waitCount = 0;
        $serviceCount = 0;

        foreach ($finishedVisits as $v) {
            if ($v->started_at && $v->created_at) {
                $wait = $v->created_at->diffInMinutes($v->started_at);
                // Hanya hitung jika selisih masuk akal (misal <= 240 menit)
                if ($wait >= 0 && $wait <= 300) {
                    $totalWaitMinutes += $wait;
                    $waitCount++;
                }
            }

            if ($v->finished_at && $v->started_at) {
                $service = $v->started_at->diffInMinutes($v->finished_at);
                if ($service >= 0 && $service <= 300) {
                    $totalServiceMinutes += $service;
                    $serviceCount++;
                }
            }
        }

        $avgWaitTime = $waitCount > 0 ? round($totalWaitMinutes / $waitCount) : 0;
        $avgServiceTime = $serviceCount > 0 ? round($totalServiceMinutes / $serviceCount) : 0;

        // 3. Data Tren 7 Hari Terakhir untuk Chart.js
        $trendDates = [];
        $trendKiosk = [];
        $trendOnline = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $label = Carbon::parse($d)->translatedFormat('d M');
            $trendDates[] = $label;

            $kioskCount = Visit::whereDate('visit_date', $d)->where('channel', 'kiosk')->count();
            $onlineCount = Visit::whereDate('visit_date', $d)->where('channel', 'online')->count();

            $trendKiosk[] = $kioskCount;
            $trendOnline[] = $onlineCount;
        }

        // 4. Distribusi Jenis Layanan Bulan Ini
        $serviceDistribution = Visit::whereDate('visit_date', '>=', $startOfMonth)
            ->select('purpose', 'service_type', DB::raw('count(*) as total'))
            ->groupBy('purpose', 'service_type')
            ->orderByDesc('total')
            ->get();

        $serviceLabels = [];
        $serviceValues = [];
        foreach ($serviceDistribution as $item) {
            $name = $item->service_type ?: ($item->purpose === 'pst' ? 'Layanan PST Lainnya' : ($item->purpose === 'pengaduan' ? 'Pengaduan' : 'Non-PST'));
            $serviceLabels[] = $name;
            $serviceValues[] = $item->total;
        }

        // 5. Distribusi Asal Instansi Terbanyak (Top 5)
        $topInstitutions = Visit::whereDate('visit_date', '>=', $startOfMonth)
            ->select('institution', DB::raw('count(*) as total'))
            ->groupBy('institution')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 6. Petugas Piket Hari Ini (Memperhitungkan Swap)
        $onDutyOfficers = $this->dutyScheduleService->getEffectiveOfficersForDate($today);

        // 7. Swap Terbaru (5 swap terakhir)
        $recentSwaps = DutySwap::with(['originalOfficer', 'replacementOfficer', 'swappedBy'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'today',
            'todayCount',
            'weekCount',
            'monthCount',
            'activeCount',
            'avgWaitTime',
            'avgServiceTime',
            'trendDates',
            'trendKiosk',
            'trendOnline',
            'serviceLabels',
            'serviceValues',
            'topInstitutions',
            'onDutyOfficers',
            'recentSwaps'
        ));
    }
}
