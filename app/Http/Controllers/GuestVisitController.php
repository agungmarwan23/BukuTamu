<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestVisitRequest;
use App\Models\Visit;
use App\Models\VisitAttachment;
use App\Models\VisitStatusLog;
use App\Services\QueueNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuestVisitController extends Controller
{
    public function __construct(
        protected QueueNumberService $queueService,
    ) {}

    /**
     * Tampilkan form registrasi tamu di PC / Tablet Kios Lobi.
     */
    public function kioskForm(): View
    {
        return view('kiosk.form');
    }

    /**
     * Tampilkan form registrasi layanan online dari luar kantor.
     */
    public function onlineForm(): View
    {
        return view('online.form');
    }

    /**
     * Simpan pendaftaran kunjungan tamu (kios maupun online).
     */
    public function store(StoreGuestVisitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $channel = $validated['channel'];
        $today = now()->toDateString();

        $visit = DB::transaction(function () use ($request, $validated, $channel, $today) {
            // Generate kode tiket unik
            $prefix = $channel === 'kiosk' ? 'KIO' : 'ONL';
            $datePart = now()->format('Ymd');
            
            // Hitung urutan per tanggal dan saluran untuk kode tiket
            $countToday = Visit::where('visit_date', $today)
                ->where('channel', $channel)
                ->lockForUpdate()
                ->count();
            $seq = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
            $ticketCode = "{$prefix}-{$datePart}-{$seq}";

            // Pastikan kode tiket unik
            while (Visit::where('ticket_code', $ticketCode)->exists()) {
                $randomSuffix = strtoupper(Str::random(3));
                $ticketCode = "{$prefix}-{$datePart}-{$randomSuffix}";
            }

            // Generate nomor antrean harian untuk tamu kios
            $queueData = ['queue_seq' => null, 'queue_number' => null];
            if ($channel === 'kiosk') {
                $queueData = $this->queueService->generateNext($today);
            }

            // Simpan foto privat jika ada (dari webcam base64)
            $photoPath = null;
            if (! empty($validated['photo'])) {
                $photoData = $validated['photo'];
                if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
                    $photoData = substr($photoData, strpos($photoData, ',') + 1);
                    $extension = strtolower($type[1]);
                    if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $extension = 'jpg';
                    }
                    $decodedImage = base64_decode($photoData);

                    if ($decodedImage !== false) {
                        $monthFolder = now()->format('Y-m');
                        $photoName = 'photos/' . $monthFolder . '/' . $datePart . '_' . Str::random(16) . '.' . $extension;
                        Storage::disk('local')->put($photoName, $decodedImage);
                        $photoPath = $photoName;
                    }
                }
            }

            // Tentukan saluran PST otomatis dari pintu masuk
            $pstChannel = null;
            if ($validated['purpose'] === 'pst') {
                $pstChannel = $channel === 'kiosk' ? 'offline' : 'online';
            }

            // Simpan data kunjungan
            $visit = Visit::create([
                'visit_date' => $today,
                'channel' => $channel,
                'ticket_code' => $ticketCode,
                'queue_seq' => $queueData['queue_seq'],
                'queue_number' => $queueData['queue_number'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'institution' => $validated['institution'],
                'purpose' => $validated['purpose'],
                'pst_channel' => $pstChannel,
                'service_type' => $validated['service_type'] ?? null,
                'non_pst_type' => $validated['non_pst_type'] ?? null,
                'detail' => $validated['detail'],
                'complaint_title' => $validated['complaint_title'] ?? null,
                'photo_path' => $photoPath,
                'consent_at' => now(),
                'status' => 'menunggu',
            ]);

            // Simpan lampiran berkas privat jika ada
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $monthFolder = now()->format('Y-m');
                $attachmentPath = $file->store('attachments/' . $monthFolder, 'local');

                VisitAttachment::create([
                    'visit_id' => $visit->id,
                    'path' => $attachmentPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            // Catat log status awal
            VisitStatusLog::create([
                'visit_id' => $visit->id,
                'from_status' => null,
                'to_status' => 'menunggu',
                'user_id' => null,
                'created_at' => now(),
            ]);

            return $visit;
        });

        if ($channel === 'kiosk') {
            return redirect()->route('kios.success', ['ticket_code' => $visit->ticket_code]);
        }

        return redirect()->route('layanan.success', ['ticket_code' => $visit->ticket_code]);
    }

    /**
     * Tampilan sukses registrasi di Kios Lobi.
     */
    public function kioskSuccess(string $ticket_code): View
    {
        $visit = Visit::where('ticket_code', $ticket_code)->firstOrFail();

        return view('kiosk.success', compact('visit'));
    }

    /**
     * Tampilan sukses registrasi form online luar kantor.
     */
    public function onlineSuccess(string $ticket_code): View
    {
        $visit = Visit::where('ticket_code', $ticket_code)->firstOrFail();

        return view('online.success', compact('visit'));
    }
}
