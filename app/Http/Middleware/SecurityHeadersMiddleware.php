<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Tambahkan header keamanan dasar ke setiap respons HTTP.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Cegah browser menebak MIME type (mencegah MIME sniffing attacks)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Cegah clickjacking: DENY secara default, SAMEORIGIN untuk kios dan layar TV
        $framePolicy = $this->shouldAllowFraming($request) ? 'SAMEORIGIN' : 'DENY';
        $response->headers->set('X-Frame-Options', $framePolicy);

        // Aktifkan filter XSS bawaan browser (lapisan pertahanan tambahan)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Kontrol informasi referer yang dikirim antar situs
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Batasi akses fitur browser — kamera hanya untuk origin sendiri (kios), mikrofon diblokir
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // Hindari caching halaman sensitif (respons HTML dengan auth)
        if ($request->is('admin/*', 'resepsionis/*', 'petugas/*', 'profile*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    /**
     * Halaman kios dan layar TV boleh ditampilkan dalam iframe (mode kios/signage).
     */
    protected function shouldAllowFraming(Request $request): bool
    {
        return $request->is('kios', 'kios/*', 'layar', 'layar/*');
    }
}
