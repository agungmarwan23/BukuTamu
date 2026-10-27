<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar akun pengguna sistem (FR-35).
     */
    public function index(Request $request): View
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('username', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Tambah akun pengguna baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'nullable|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'resepsionis', 'petugas'])],
            'password' => ['required', Password::min(6)],
        ], [
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'username.alpha_dash' => 'Username hanya boleh berupa huruf, angka, tanda strip (-), atau garis bawah (_).',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return back()->with('success', "Akun pengguna {$validated['username']} berhasil dibuat.");
    }

    /**
     * Aktifkan / Nonaktifkan akun pengguna.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->username} berhasil {$statusText}.");
    }

    /**
     * Reset kata sandi pengguna.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'new_password' => ['required', Password::min(6)],
        ], [
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return back()->with('success', "Kata sandi untuk pengguna {$user->username} berhasil diatur ulang.");
    }
}
