<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitStatusLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        if ($request->filled('is_imported')) {
            if ($request->input('is_imported') === '1') {
                $query->where('is_imported', true);
            } elseif ($request->input('is_imported') === '0') {
                $query->where('is_imported', false);
            }
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

        $officers = User::whereIn('role', ['petugas', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.visits.show', compact('visit', 'officers'));
    }

    /**
     * Memperbarui status, petugas penanggung jawab, dan catatan layanan oleh Admin.
     */
    public function update(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:menunggu,dipanggil,dilayani,selesai,tidak_hadir,dibatalkan',
            'assigned_officer_id' => 'nullable|exists:users,id',
            'officer_note' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $visit->status;
        $newStatus = $validated['status'];

        $updates = [
            'status' => $newStatus,
            'assigned_officer_id' => $validated['assigned_officer_id'] ?? null,
            'officer_note' => $validated['officer_note'] ?? null,
        ];

        if ($newStatus === 'dilayani' && ! $visit->started_at) {
            $updates['started_at'] = now();
        }

        if ($newStatus === 'selesai' && ! $visit->finished_at) {
            $updates['finished_at'] = now();
        }

        $visit->update($updates);

        if ($oldStatus !== $newStatus) {
            VisitStatusLog::create([
                'visit_id' => $visit->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);
        }

        return back()->with('success', "Data kunjungan {$visit->ticket_code} berhasil diperbarui.");
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
                'Sumber Data',
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
                        $v->is_imported ? 'IMPOR (' . ($v->import_source ?: 'Spreadsheet') . ')' : 'LANGSUNG',
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

    /**
     * Unduh contoh template CSV untuk impor data kunjungan/buku tamu lama.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'template_impor_kunjungan_' . now()->format('Ymd') . '.csv';

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

            fputcsv($handle, ['tanggal', 'nama_lengkap', 'instansi', 'no_hp', 'email', 'tujuan', 'jenis_layanan', 'rincian_keperluan', 'status']);

            fputcsv($handle, [now()->format('Y-m-d'), 'Budi Setiawan', 'Bappeda Inhil', '081234567890', 'budi@example.com', 'pst', 'Konsultasi Statistik', 'Konsultasi data kemiskinan makro', 'selesai']);
            fputcsv($handle, [now()->format('Y-m-d'), 'Siti Rahmah', 'Dinas Pertanian', '081298765432', '', 'non_pst', 'Kerjasama', 'Koordinasi data sensus pertanian', 'selesai']);
            fputcsv($handle, [now()->format('Y-m-d'), 'Ahmad Fauzi', 'Masyarakat Umum', '085211223344', '', 'pengaduan', '', 'Layanan konsultasi data', 'selesai']);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Impor data kunjungan massal dari file CSV riwayat spreadsheet.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
            'source_name' => 'nullable|string|max:100',
        ], [
            'csv_file.required' => 'Pilih file CSV yang akan diimpor.',
            'csv_file.mimes' => 'Format file harus berupa CSV (.csv atau .txt).',
            'csv_file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('csv_file');
        $sourceName = trim((string) $request->input('source_name')) ?: 'Impor Spreadsheet Lama';

        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 2000, ',');
        $delimiter = ',';
        if (! $header || count($header) <= 1) {
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }
            $header = fgetcsv($handle, 2000, ';');
            $delimiter = ';';
        }

        if (! $header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        $cols = array_map(fn ($c) => strtolower(trim((string) $c)), $header);
        $findCol = function (array $aliases) use ($cols) {
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $cols, true);
                if ($idx !== false) {
                    return $idx;
                }
            }
            return false;
        };

        $idxDate = $findCol(['tanggal', 'tgl', 'date', 'visit_date']);
        $idxName = $findCol(['nama_lengkap', 'nama', 'name', 'full_name']);
        $idxInst = $findCol(['instansi', 'asal_instansi', 'institution']);
        $idxPhone = $findCol(['no_hp', 'telepon', 'hp', 'phone', 'no_telepon']);
        $idxEmail = $findCol(['email', 'surel']);
        $idxPurpose = $findCol(['tujuan', 'tujuan_layanan', 'purpose']);
        $idxService = $findCol(['jenis_layanan', 'layanan', 'service_type']);
        $idxDetail = $findCol(['rincian_keperluan', 'keperluan', 'rincian', 'detail']);
        $idxStatus = $findCol(['status']);

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2000, $delimiter)) !== false) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $name = $idxName !== false ? trim($row[$idxName] ?? '') : '';
                if (empty($name)) {
                    $skipped++;
                    continue;
                }

                // Tanggal
                $rawDate = $idxDate !== false ? trim($row[$idxDate] ?? '') : '';
                try {
                    $visitDate = ! empty($rawDate) ? Carbon::parse($rawDate)->toDateString() : now()->toDateString();
                } catch (\Exception $e) {
                    $visitDate = now()->toDateString();
                }

                $inst = $idxInst !== false ? trim($row[$idxInst] ?? '') : 'Umum';
                $phone = $idxPhone !== false ? trim($row[$idxPhone] ?? '') : '-';
                if (empty($phone)) {
                    $phone = '-';
                }
                $email = $idxEmail !== false ? trim($row[$idxEmail] ?? '') : null;

                // Tujuan
                $rawPurpose = $idxPurpose !== false ? strtolower(trim($row[$idxPurpose] ?? '')) : 'pst';
                $purpose = match ($rawPurpose) {
                    'non_pst', 'non pst', 'non-pst', 'kerjasama' => 'non_pst',
                    'pengaduan', 'aduan', 'complaint' => 'pengaduan',
                    default => 'pst',
                };

                $serviceType = $idxService !== false ? trim($row[$idxService] ?? '') : null;
                $detail = $idxDetail !== false ? trim($row[$idxDetail] ?? '') : 'Data riwayat kunjungan impor.';

                // Status
                $rawStatus = $idxStatus !== false ? strtolower(trim($row[$idxStatus] ?? '')) : 'selesai';
                $status = in_array($rawStatus, ['menunggu', 'dipanggil', 'dilayani', 'selesai', 'tidak_hadir', 'dibatalkan']) 
                    ? $rawStatus 
                    : 'selesai';

                $ticketCode = 'IMP-' . date('Ymd', strtotime($visitDate)) . '-' . strtoupper(Str::random(5));

                $visit = Visit::create([
                    'visit_date' => $visitDate,
                    'channel' => 'kiosk',
                    'ticket_code' => $ticketCode,
                    'queue_number' => null,
                    'full_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'institution' => $inst ?: 'Umum',
                    'purpose' => $purpose,
                    'service_type' => $serviceType,
                    'detail' => $detail,
                    'status' => $status,
                    'is_imported' => true,
                    'import_source' => $sourceName,
                    'consent_at' => Carbon::parse($visitDate)->setTime(8, 0),
                    'finished_at' => $status === 'selesai' ? Carbon::parse($visitDate)->setTime(9, 0) : null,
                ]);

                VisitStatusLog::create([
                    'visit_id' => $visit->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'user_id' => Auth::id(),
                    'created_at' => Carbon::parse($visitDate)->setTime(9, 0),
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Gagal memproses impor kunjungan: ' . $e->getMessage());
        }

        return back()->with('success', "Berhasil mengimpor {$imported} data riwayat kunjungan ke dalam sistem (Label Sumber: {$sourceName}).");
    }
}
