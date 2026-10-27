<?php

namespace App\Http\Controllers;

use App\Models\KioskDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class KioskDeviceController extends Controller
{
    /**
     * Aktivasi perangkat kios melalui token satu kali pakai.
     */
    public function activate(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);
        $device = KioskDevice::where('token_hash', $hash)->first();

        if (! $device) {
            abort(403, 'Token aktivasi perangkat kios tidak valid.');
        }

        if (! $device->is_active) {
            abort(403, 'Perangkat kios ini telah dinonaktifkan.');
        }

        $device->update([
            'last_seen_at' => now(),
        ]);

        // Simpan token di cookie HTTP-only selama 1 tahun (525600 menit)
        $cookie = cookie(
            'kiosk_device_token',
            $token,
            525600, // 1 tahun
            null,
            null,
            $request->isSecure(),
            true, // httpOnly
            false,
            'lax'
        );

        return redirect()->route('kios.index')
            ->withCookie($cookie)
            ->with('status', "Perangkat \"{$device->name}\" berhasil diaktivasi sebagai Kios resmi BPS Inhil!");
    }
}
