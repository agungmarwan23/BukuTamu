<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitController extends Controller
{
    /**
     * Membangun query terfilter untuk daftar kunjungan.
     */
    protected function buildFilteredQuery(Request $request): Builder
    {
        $query = Visit::query()
            ->with(['assignedOfficer', 'attachments', 'statusLogs.user'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id');

        if ($request->filled('start_date')) {
            $query->whereDate('visit_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('visit_date', '<=', $request->input('end_date'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->input('purpose'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('officer_id')) {
            $query->where('assigned_officer_id', $request->input('officer_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('institution', 'like', $search)
                  ->orWhere('ticket_code', 'like', $search)
                  ->orWhere('queue_number', 'like', $search)
                  ->orWhere('phone', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        return $query;
    }

    /**
     * Daftar seluruh kunjungan tamu dengan filter & pencarian (FR-30).
     */
    public function index(Request $request): View
    {
        $visits = $this->buildFilteredQuery($request)->paginate(20)->withQueryString();

        $officers = User::whereIn('role', ['petugas', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.visits.index', compact('visits', 'officers'));
    }

    /**
     * Halaman detail kunjungan tamu lengkap (FR-30).
     */
    public function show(Visit $visit): View
    {
        $visit->load(['assignedOfficer', 'attachments', 'statusLogs.user', 'callEvents.caller']);

        return view('admin.visits.show', compact('visit'));
    }

    /**
     * Ekspor daftar kunjungan ke CSV (FR-34) mengikuti filter aktif.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildFilteredQuery($request);

        $filename = 'daftar_kunjungan_bps_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Tulis UTF-8 BOM agar Excel menampilkan aksara dengan benar
            fputs($handle, "\xEF\xBB\xBF");

            // Header Kolom CSV
            fputcsv($handle, [
                'No',
                'Tanggal Kunjungan',
                'Saluran',
                'No. Antrean',
                'Kode Tiket',
                'Nama Tamu',
                'Instansi',
                'No. HP',
                'Email',
                'Tujuan',
                'Jenis Layanan',
                'Rincian Keperluan',
                'Petugas Melayani',
                'Status',
                'Waktu Registrasi',
                'Waktu Dipanggil',
                'Waktu Mulai Layani',
                'Waktu Selesai',
                'Catatan Petugas',
            ], ';');

            $rowNumber = 1;

            $query->chunk(200, function ($visits) use ($handle, &$rowNumber) {
                foreach ($visits as $v) {
                    fputcsv($handle, [
                        $rowNumber++,
                        $v->visit_date ? $v->visit_date->format('Y-m-d') : '',
                        strtoupper($v->channel),
                        $v->queue_number ?: '-',
                        $v->ticket_code,
                        $v->full_name,
                        $v->institution ?: '-',
                        $v->phone,
                        $v->email ?: '-',
                        strtoupper(str_replace('_', ' ', $v->purpose)),
                        $v->service_type ?: ($v->non_pst_type ?: '-'),
                        $v->detail,
                        $v->assignedOfficer?->name ?: 'Belum Ditugaskan',
                        strtoupper(str_replace('_', ' ', $v->status)),
                        $v->created_at ? $v->created_at->format('Y-m-d H:i:s') : '',
                        $v->called_at ? $v->called_at->format('Y-m-d H:i:s') : '-',
                        $v->started_at ? $v->started_at->format('Y-m-d H:i:s') : '-',
                        $v->finished_at ? $v->finished_at->format('Y-m-d H:i:s') : '-',
                        $v->officer_note ?: '-',
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
