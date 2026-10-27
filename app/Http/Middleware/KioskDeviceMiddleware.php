<?php

namespace App\Http\Middleware;

use App\Models\KioskDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KioskDeviceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('kiosk_device_token');

        if (empty($token)) {
            abort(403, 'Akses Ditolak: Perangkat ini belum didaftarkan sebagai kios resmi. Silakan hubungi Administrator untuk aktivasi perangkat.');
        }

        $hash = hash('sha256', $token);
        $device = KioskDevice::where('token_hash', $hash)->where('is_active', true)->first();

        if (! $device) {
            abort(403, 'Akses Ditolak: Token perangkat kios tidak valid atau telah dinonaktifkan.');
        }

        // Perbarui last_seen_at berkala (hanya jika sudah > 2 menit dari update terakhir)
        if (! $device->last_seen_at || $device->last_seen_at->diffInMinutes(now()) >= 2) {
            $device->update(['last_seen_at' => now()]);
        }

        $request->attributes->set('kiosk_device', $device);

        return $next($request);
    }
}
