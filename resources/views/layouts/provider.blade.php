<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Provider') — RSRV Provider</title>

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
                        pvdr: { 50:'#f0fdf4', 100:'#dcfce7', 200:'#bbf7d0', 300:'#86efac', 400:'#4ade80', 500:'#22c55e', 600:'#16a34a', 700:'#15803d', 800:'#166534', 900:'#14532d' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 10px;
            font-size: 0.875rem; font-weight: 500;
            color: #4b5563; transition: all .15s;
        }
        .sidebar-link:hover { background: #f0fdf4; color: #15803d; }
        .sidebar-link.active { background: #16a34a; color: #fff; }
        .sidebar-link .icon { width: 20px; text-align: center; }
        .card { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    </style>
</head>
<body class="bg-slate-100 min-h-screen" x-data="authStore()" x-init="init()">

<div class="flex min-h-screen">

    {{-- ── SIDEBAR ── --}}
    <aside class="hidden md:flex flex-col w-60 bg-white border-r border-slate-200 fixed inset-y-0 left-0 z-30">

        {{-- Logo --}}
        <div class="h-16 flex items-center px-5 border-b border-slate-100 flex-shrink-0">
            <a href="{{ url('/provider/dashboard') }}" class="flex items-center gap-2.5 font-black text-lg tracking-tight text-pvdr-700">
                <span class="bg-pvdr-600 text-white rounded-lg w-8 h-8 flex items-center justify-center text-sm">R</span>
                <span>RSRV <span class="text-pvdr-500 font-normal text-sm">Provider</span></span>
            </a>
        </div>

        {{-- User info --}}
        <div class="px-4 py-3 border-b border-slate-100 flex-shrink-0" x-cloak>
            <template x-if="user">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-pvdr-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0"
                         x-text="user.name.charAt(0).toUpperCase()"></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold truncate" x-text="user.name"></p>
                        <p class="text-xs text-slate-400">Provider</p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
            <a href="{{ url('/provider/dashboard') }}"
               class="sidebar-link {{ request()->is('provider/dashboard') ? 'active' : '' }}">
               <span class="icon">📊</span> Dashboard
            </a>
            <a href="{{ url('/provider/profile') }}"
               class="sidebar-link {{ request()->is('provider/profile') ? 'active' : '' }}">
               <span class="icon">🏢</span> Profil Bisnis
            </a>

            <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-2">Katalog Saya</p>
            <a href="{{ url('/provider/resources') }}"
               class="sidebar-link {{ request()->is('provider/resources*') ? 'active' : '' }}">
               <span class="icon">📦</span> Kelola Menu / Layanan
            </a>
            <a href="{{ url('/provider/resources/create') }}"
               class="sidebar-link {{ request()->is('provider/resources/create') ? 'active' : '' }}">
               <span class="icon">➕</span> Tambah Layanan Baru
            </a>

            <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-2">Pemesanan</p>
            <a href="{{ url('/provider/bookings') }}"
               class="sidebar-link {{ request()->is('provider/bookings*') ? 'active' : '' }}">
               <span class="icon">📋</span> Booking Masuk
            </a>
        </nav>

        {{-- Logout --}}
        <div class="px-3 py-4 border-t border-slate-100 flex-shrink-0">
            <a href="{{ url('/resources') }}" class="sidebar-link mb-1">
                <span class="icon">🔍</span> Lihat sebagai Customer
            </a>
            <button @click="logout()" class="sidebar-link w-full text-red-500 hover:bg-red-50 hover:text-red-600">
                <span class="icon">🚪</span> Logout
            </button>
        </div>
    </aside>

    {{-- ── MAIN AREA ── --}}
    <div class="flex-1 md:ml-60 flex flex-col min-h-screen">

        {{-- Topbar mobile --}}
        <header class="md:hidden bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 sticky top-0 z-20">
            <a href="{{ url('/provider/dashboard') }}" class="font-black text-lg text-pvdr-700">
                RSRV <span class="text-pvdr-500 font-normal text-sm">Provider</span>
            </a>
            <div class="flex items-center gap-2" x-data="{ open: false }">
                <button @click="open = !open" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                {{-- Mobile dropdown --}}
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute top-14 right-0 left-0 bg-white border-b border-slate-200 shadow-lg px-4 py-3 space-y-1 z-40">
                    <a href="{{ url('/provider/dashboard') }}" class="sidebar-link">📊 Dashboard</a>
                    <a href="{{ url('/provider/profile') }}" class="sidebar-link">🏢 Profil Bisnis</a>
                    <a href="{{ url('/provider/resources') }}" class="sidebar-link">📦 Kelola Layanan</a>
                    <a href="{{ url('/provider/resources/create') }}" class="sidebar-link">➕ Tambah Layanan</a>
                    <a href="{{ url('/provider/bookings') }}" class="sidebar-link">📋 Booking Masuk</a>
                    <hr class="border-slate-100">
                    <button @click="logout()" class="sidebar-link w-full text-red-500">🚪 Logout</button>
                </div>
            </div>
        </header>

        {{-- Page header slot --}}
        @hasSection('page-header')
        <div class="bg-white border-b border-slate-200 px-6 py-5">
            @yield('page-header')
        </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 px-4 md:px-6 py-6">
            @yield('content')
        </main>

        <footer class="text-center text-xs text-slate-400 py-4">
            © {{ date('Y') }} RSRV Provider Panel
        </footer>
    </div>
</div>

<script>
    function authStore() {
        return {
            token: localStorage.getItem('token'),
            user: JSON.parse(localStorage.getItem('user') || 'null'),
            init() {
                // Redirect ke login jika tidak ada token
                if (!this.token && !window.location.pathname.includes('/login')) {
                    // skip di halaman publik
                }
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

    window.formatRupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
</script>

@stack('scripts')
</body>
</html>
