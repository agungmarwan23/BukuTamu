<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-blue-900 rounded-full inline-block"></span>
                    {{ __('Kelola Akun Pengguna Sistem') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Tambah akun baru, atur hak akses (peran), nonaktifkan, atau atur ulang kata sandi (FR-35).
                </p>
            </div>
            <div>
                <button type="button" @click="$dispatch('open-user-modal')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <span>+ Tambah Akun Baru</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ showAddModal: false, activeResetModal: null, selectedUser: null }" @open-user-modal.window="showAddModal = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    <div class="font-semibold mb-1">Terjadi kesalahan input:</div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Pencarian & Filter Peran -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 max-w-sm">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari nama, username, atau email..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div>
                        <select name="role" class="text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Peran</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="resepsionis" {{ request('role') === 'resepsionis' ? 'selected' : '' }}>Resepsionis</option>
                            <option value="petugas" {{ request('role') === 'petugas' ? 'selected' : '' }}>Petugas</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'role']))
                        <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
                    @endif
                </form>
            </div>

            <!-- Tabel Pengguna -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3.5">Nama & Username</th>
                                <th class="px-4 py-3.5">Kontak</th>
                                <th class="px-4 py-3.5">Peran</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-50/70 transition {{ ! $user->is_active ? 'bg-slate-50 opacity-60' : '' }}">
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                            {{ $user->name }}
                                            @if ($user->id === Auth::id())
                                                <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold">Anda</span>
                                            @endif
                                        </div>
                                        <div class="font-mono text-slate-500 text-[11px] mt-0.5">
                                            {{ '@' . $user->username }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="text-slate-800">{{ $user->email ?: '-' }}</div>
                                        <div class="text-slate-500">{{ $user->phone ?: '-' }}</div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'resepsionis' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800') }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5 text-center">
                                        @if ($user->is_active)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Reset Password Button -->
                                            <button type="button"
                                                @click="activeResetModal = {{ $user->id }}; selectedUser = {{ json_encode(['id' => $user->id, 'name' => $user->name, 'username' => $user->username]) }}"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                                Reset Sandi
                                            </button>

                                            <!-- Toggle Status Button -->
                                            @if ($user->id !== Auth::id())
                                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $user->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                                        onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif akun ini?');">
                                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $users->links() }}
                </div>
            </div>

            <!-- MODAL: Tambah Akun Baru -->
            <div x-show="showAddModal" x-cloak
                class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showAddModal = false">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Tambah Akun Pengguna Baru</h3>

                    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="Contoh: Budi Santoso, S.Stat."
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username (Login) <span class="text-rose-500">*</span></label>
                            <input type="text" name="username" required placeholder="budi_pst"
                                class="w-full text-xs rounded-xl border-slate-300 font-mono focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email</label>
                                <input type="email" name="email" placeholder="budi@bps.go.id"
                                    class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor HP</label>
                                <input type="text" name="phone" placeholder="08123456789"
                                    class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Peran Akun (Role) <span class="text-rose-500">*</span></label>
                            <select name="role" required class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="petugas">Petugas Layanan PST</option>
                                <option value="resepsionis">Resepsionis / Front Office</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi Awal <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" required placeholder="Minimal 6 karakter"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-sm">
                                Simpan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Reset Password -->
            <div x-show="activeResetModal" x-cloak
                class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="activeResetModal = null">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Atur Ulang Kata Sandi</h3>
                    <p class="text-xs text-slate-500" x-text="'Pengguna: ' + (selectedUser ? selectedUser.name : '') + ' (@' + (selectedUser ? selectedUser.username : '') + ')'"></p>

                    <form method="POST" :action="'/admin/pengguna/' + activeResetModal + '/reset-password'" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi Baru</label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter baru"
                                class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="activeResetModal = null" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm">
                                Simpan Sandi Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
