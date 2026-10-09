<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RSRV') — Customer</title>
    <meta name="description" content="@yield('meta_description', 'Platform booking jasa & tempat terbaik.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 200:'#bfdbfe', 300:'#93c5fd', 400:'#60a5fa', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 800:'#1e40af', 900:'#1e3a8a' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- Midtrans Snap.js --}}
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .nav-link { @apply flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-brand-50 hover:text-brand-700 transition-colors; }
        .nav-link.active { @apply bg-brand-600 text-white hover:bg-brand-700 hover:text-white; }
        .card { @apply bg-white rounded-2xl border border-slate-100 shadow-sm; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col" x-data="authStore()" x-init="init()">

    {{-- ── TOPBAR ── --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between gap-4">

            {{-- Logo --}}
            <a href="{{ url('/resources') }}" class="flex items-center gap-2 font-black text-xl tracking-tight text-brand-700">
                <span class="bg-brand-600 text-white rounded-lg w-8 h-8 flex items-center justify-center text-sm">R</span>
                RSRV
            </a>

            {{-- Nav tengah --}}
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ url('/resources') }}"
                   class="nav-link {{ request()->is('resources*') ? 'active' : '' }}">
                   🔍 Cari Layanan
                </a>
                <template x-if="token">
                    <a href="{{ url('/bookings') }}"
                       class="nav-link {{ request()->is('bookings*') ? 'active' : '' }}">
                       📋 Booking Saya
                    </a>
                </template>
            </nav>

            {{-- User area --}}
            <div class="flex items-center gap-3">
                <template x-if="token && user && user.role === 'provider'">
                    <a href="{{ url('/provider/dashboard') }}"
                       class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                        ← Dashboard Provider
                    </a>
                </template>

                <template x-if="token && user && user.role === 'admin'">
                    <a href="{{ url('/admin/dashboard') }}"
                       class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800">
                        ← Dashboard Admin
                    </a>
                </template>

                <template x-if="token">
                    <div class="flex items-center gap-3" x-cloak>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 transition px-3 py-1.5 rounded-full text-sm font-medium">
                                <span class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold"
                                      x-text="user ? user.name.charAt(0).toUpperCase() : 'U'"></span>
                                <span x-text="user ? user.name.split(' ')[0] : ''" class="max-w-[80px] truncate"></span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden text-sm">
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <p class="font-semibold" x-text="user ? user.name : ''"></p>
                                    <p class="text-slate-400 text-xs" x-text="user ? user.email : ''"></p>
                                </div>
                                <a href="{{ url('/bookings') }}" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50">📋 Booking Saya</a>
                                <hr class="border-slate-100">
                                <button @click="logout()" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-red-600 hover:bg-red-50">🚪 Logout</button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!token">
                    <div class="flex items-center gap-2" x-cloak>
                        <a href="{{ url('/login') }}" class="text-sm font-medium text-slate-600 hover:text-brand-700">Masuk</a>
                        <a href="{{ url('/register') }}" class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Daftar</a>
                    </div>
                </template>

                {{-- Mobile menu --}}
                <button class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100" x-data @click="$dispatch('toggle-mobile-menu')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile nav --}}
        <div x-data="{ open: false }" @toggle-mobile-menu.window="open = !open">
            <div x-show="open" x-cloak class="md:hidden border-t border-slate-100 bg-white px-4 py-3 space-y-1">
                <a href="{{ url('/resources') }}" class="nav-link">🔍 Cari Layanan</a>
                <template x-if="token">
                    <a href="{{ url('/bookings') }}" class="nav-link">📋 Booking Saya</a>
                </template>
                <template x-if="token && user && user.role === 'provider'">
                    <a href="{{ url('/provider/dashboard') }}" class="nav-link text-emerald-700">← Dashboard Provider</a>
                </template>
                <template x-if="token && user && user.role === 'admin'">
                    <a href="{{ url('/admin/dashboard') }}" class="nav-link">← Dashboard Admin</a>
                </template>
                <template x-if="token">
                    <button @click="logout()" class="nav-link w-full text-red-600">🚪 Logout</button>
                </template>
            </div>
        </div>
    </header>

    {{-- ── CONTENT ── --}}
    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-6">
        @yield('content')
    </main>

    {{-- ── FOOTER ── --}}
    <footer class="bg-white border-t border-slate-100 mt-auto">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
            <span>© {{ date('Y') }} RSRV — Platform Booking Layanan</span>
            <span>Made by Olaaw</span>
        </div>
    </footer>

    <script>
        function authStore() {
            return {
                token: localStorage.getItem('token'),
                user: JSON.parse(localStorage.getItem('user') || 'null'),
                init() {},
                setAuth(token, user) {
                    this.token = token; this.user = user;
                    localStorage.setItem('token', token);
                    localStorage.setItem('user', JSON.stringify(user));
                },
                logout() {
                    apiFetch('/logout', { method: 'POST' }).finally(() => {
                        localStorage.removeItem('token');
                        localStorage.removeItem('user');
                        window.location.href = '{{ url('/login') }}';
                    });
                },
            };
        }

        window.apiFetch = async function(path, options = {}) {
            const token = localStorage.getItem('token');
            const res = await fetch('/api' + path, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
                    ...(options.headers || {}),
                },
                body: options.body ? JSON.stringify(options.body) : undefined,
            });
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                const error = new Error((data && data.message) || 'Terjadi kesalahan.');
                error.status = res.status; error.data = data;
                throw error;
            }
            return data;
        };
    </script>

    @stack('scripts')
</body>
</html>
