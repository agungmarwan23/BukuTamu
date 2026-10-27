<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KioskDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KioskDeviceManageController extends Controller
{
    /**
     * Daftar perangkat kios lobi yang terdaftar.
     */
    public function index(): View
    {
        $devices = KioskDevice::with('activator')->orderByDesc('id')->get();

        return view('admin.kiosk_devices.index', compact('devices'));
    }

    /**
     * Daftarkan perangkat kios baru dan buatkan token aktivasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'Nama perangkat kios wajib diisi (misal: PC Kios Lobi 1).',
        ]);

        $plainToken = Str::random(40);
        $tokenHash = hash('sha256', $plainToken);

        $device = KioskDevice::create([
            'name' => $request->input('name'),
            'token_hash' => $tokenHash,
            'is_active' => true,
        ]);

        $activationUrl = route('kios.activate', ['token' => $plainToken]);

        return back()
            ->with('success', "Perangkat '{$device->name}' berhasil didaftarkan.")
            ->with('activation_url', $activationUrl)
            ->with('device_name', $device->name);
    }

    /**
     * Aktifkan / Nonaktifkan akses perangkat kios.
     */
    public function toggleStatus(KioskDevice $device): RedirectResponse
    {
        $device->update([
            'is_active' => ! $device->is_active,
        ]);

        $statusText = $device->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return back()->with('success', "Akses perangkat '{$device->name}' {$statusText}.");
    }

    /**
     * Hapus perangkat kios.
     */
    public function destroy(KioskDevice $device): RedirectResponse
    {
        $name = $device->name;
        $device->delete();

        return back()->with('success', "Perangkat '{$name}' berhasil dihapus.");
    }
}
