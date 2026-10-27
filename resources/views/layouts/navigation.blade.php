<nav x-data="{ open: false }" class="bg-white border-b border-slate-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-lg bg-blue-900 flex items-center justify-center text-white font-bold text-sm">
                            BPS
                        </div>
                        <span class="font-bold text-slate-800 text-sm hidden sm:inline-block">Buku Tamu & Antrean</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('Dasbor Admin') }}
                        </x-nav-link>
                        <x-nav-link :href="route('resepsionis.dashboard')" :active="request()->routeIs('resepsionis.*')">
                            {{ __('Resepsionis') }}
                        </x-nav-link>
                        <x-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                            {{ __('Portal Petugas') }}
                        </x-nav-link>
                    @elseif (Auth::user()->isResepsionis())
                        <x-nav-link :href="route('resepsionis.dashboard')" :active="request()->routeIs('resepsionis.*')">
                            {{ __('Meja Resepsionis') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                            {{ __('Portal Petugas') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 text-sm leading-4 font-medium rounded-lg text-slate-700 bg-white hover:text-slate-900 hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold">{{ Auth::user()->name }}</span>
                            <span class="text-xs uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">{{ Auth::user()->role }}</span>

                            <svg class="fill-current h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-slate-500 border-b border-slate-100">
                            Masuk sebagai <strong>{{ Auth::user()->username }}</strong>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil Akun') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50">
                                {{ __('Keluar (Log Out)') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 focus:text-slate-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Dasbor Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resepsionis.dashboard')" :active="request()->routeIs('resepsionis.*')">
                    {{ __('Resepsionis') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                    {{ __('Portal Petugas') }}
                </x-responsive-nav-link>
            @elseif (Auth::user()->isResepsionis())
                <x-responsive-nav-link :href="route('resepsionis.dashboard')" :active="request()->routeIs('resepsionis.*')">
                    {{ __('Meja Resepsionis') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('petugas.dashboard')" :active="request()->routeIs('petugas.*')">
                    {{ __('Portal Petugas') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-slate-200">
            <div class="px-4">
                <div class="font-semibold text-base text-slate-800">{{ Auth::user()->name }}</div>
                <div class="font-mono text-xs text-slate-500">{{ Auth::user()->username }} ({{ Auth::user()->role }})</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil Akun') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="text-red-600">
                        {{ __('Keluar (Log Out)') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
