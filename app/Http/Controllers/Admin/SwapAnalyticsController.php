<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DutySwap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SwapAnalyticsController extends Controller
{
    /**
     * Halaman Analitik Swap Piket (FR-33).
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->endOfYear()->toDateString());

        $query = DutySwap::whereDate('duty_date', '>=', $startDate)
            ->whereDate('duty_date', '<=', $endDate);

        $totalSwaps = (clone $query)->count();

        // 1. Siapa paling sering digantikan (Original Officer)
        $mostSwappedOut = (clone $query)
            ->select('original_officer_id', DB::raw('count(*) as total'))
            ->groupBy('original_officer_id')
            ->orderByDesc('total')
            ->with('originalOfficer')
            ->get();

        // 2. Siapa paling sering menggantikan (Replacement Officer)
        $mostSwappedIn = (clone $query)
            ->select('replacement_officer_id', DB::raw('count(*) as total'))
            ->groupBy('replacement_officer_id')
            ->orderByDesc('total')
            ->with('replacementOfficer')
            ->get();

        // 3. Riwayat Lengkap Swap
        $swaps = (clone $query)
            ->with(['originalOfficer', 'replacementOfficer', 'swappedBy'])
            ->orderByDesc('duty_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.analytics.swaps', compact(
            'startDate',
            'endDate',
            'totalSwaps',
            'mostSwappedOut',
            'mostSwappedIn',
            'swaps'
        ));
    }
}
