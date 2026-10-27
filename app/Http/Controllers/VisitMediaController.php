<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\VisitAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitMediaController extends Controller
{
    /**
     * Tampilkan foto privat tamu untuk petugas/admin/resepsionis yang terautentikasi.
     */
    public function photo(Visit $visit): BinaryFileResponse
    {
        if (! $visit->photo_path || ! Storage::disk('local')->exists($visit->photo_path)) {
            abort(404, 'Foto kunjungan tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($visit->photo_path);

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Unduh berkas lampiran privat kunjungan.
     */
    public function attachment(VisitAttachment $attachment): BinaryFileResponse
    {
        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Berkas lampiran tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($attachment->path);

        return response()->download($fullPath, $attachment->original_name);
    }
}
