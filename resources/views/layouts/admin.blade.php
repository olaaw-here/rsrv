<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — RSRV Admin</title>

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
                        adm: { 50:'#faf5ff', 100:'#f3e8ff', 200:'#e9d5ff', 300:'#d8b4fe', 400:'#c084fc', 500:'#a855f7', 600:'#9333ea', 700:'#7e22ce', 800:'#6b21a8', 900:'#581c87' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 8px;
            font-size: 0.8125rem; font-weight: 500;
            color: #94a3b8; transition: all .15s;
        }
        .sidebar-link:hover { background: rgba(255,255,255,.07); color: #e2e8f0; }
        .sidebar-link.active { background: #7e22ce; color: #fff; }
        .sidebar-link .icon { width: 18px; text-align: center; font-size: 14px; }
        .card { background: #1e293b; border-radius: 14px; border: 1px solid rgba(255,255,255,.06); }
        .card-white { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body class="min-h-screen" x-data="authStore()" x-init="init()">

<div class="flex min-h-screen">

    {{-- ── SIDEBAR ── --}}
    <aside class="hidden md:flex flex-col w-56 bg-slate-900 fixed inset-y-0 left-0 z-30 border-r border-white/5">

        {{-- Logo --}}
        <div class="h-16 flex items-center px-5 border-b border-white/5 flex-shrink-0">
            <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-2.5 font-black text-lg tracking-tight text-white">
                <span class="bg-adm-600 text-white rounded-lg w-8 h-8 flex items-center justify-center text-sm">A</span>
                <span>RSRV <span class="text-adm-400 font-normal text-sm">Admin</span></span>
            </a>
        </div>

        {{-- User --}}
        <div class="px-4 py-3 border-b border-white/5 flex-shrink-0" x-cloak>
            <template x-if="user">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-adm-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                         x-text="user.name.charAt(0).toUpperCase()"></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-100 truncate" x-text="user.name"></p>
                        <p class="text-xs text-slate-500">Administrator</p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
            <p class="px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Overview</p>
            <a href="{{ url('/admin/dashboard') }}"
               class="sidebar-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
               <span class="icon">📊</span> Dashboard
            </a>

            <p class="px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider mt-4 mb-2">Manajemen</p>
            <a href="{{ url('/admin/providers') }}"
               class="sidebar-link {{ request()->is('admin/providers*') ? 'active' : '' }}">
               <span class="icon">🏢</span> Verifikasi Provider
            </a>
            <a href="{{ url('/admin/refunds') }}"
               class="sidebar-link {{ request()->is('admin/refunds*') ? 'active' : '' }}">
               <span class="icon">💸</span> Refund
            </a>

            <p class="px-3 text-xs font-semibold text-slate-600 uppercase tracking-wider mt-4 mb-2">Pantau</p>
            <a href="{{ url('/resources') }}" target="_blank"
               class="sidebar-link">
               <span class="icon">🔗</span> Lihat Platform
            </a>
        </nav>

        {{-- Logout --}}
        <div class="px-3 py-4 border-t border-white/5 flex-shrink-0">
            <button @click="logout()" class="sidebar-link w-full text-red-400 hover:bg-red-900/20 hover:text-red-300">
                <span class="icon">🚪</span> Logout
            </button>
        </div>
    </aside>

    {{-- ── MAIN AREA ── --}}
    <div class="flex-1 md:ml-56 flex flex-col min-h-screen bg-slate-50">

        {{-- Topbar --}}
        <header class="bg-slate-900 border-b border-white/5 h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                {{-- Mobile menu btn --}}
                <button class="md:hidden p-1.5 rounded-lg text-slate-400 hover:bg-white/10" x-data @click="$dispatch('toggle-admin-menu')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-white font-semibold text-sm md:text-base">@yield('page-title', 'Admin Panel')</h1>
            </div>
            <div class="flex items-center gap-3" x-cloak>
                <template x-if="user">
                    <span class="text-xs text-slate-400" x-text="user.name"></span>
                </template>
                <button @click="logout()" class="text-xs text-red-400 hover:text-red-300 transition px-2 py-1">Logout</button>
            </div>
        </header>

        {{-- Mobile drawer --}}
        <div x-data="{ open: false }" @toggle-admin-menu.window="open = !open">
            <div x-show="open" @click.outside="open = false" x-cloak
                 class="md:hidden fixed inset-0 z-50 bg-slate-900/80">
                <div class="w-56 h-full bg-slate-900 border-r border-white/5 flex flex-col">
                    <div class="h-14 flex items-center px-5 border-b border-white/5 flex-shrink-0">
                        <span class="font-black text-white text-lg">RSRV Admin</span>
                    </div>
                    <nav class="flex-1 px-3 py-4 space-y-0.5">
                        <a href="{{ url('/admin/dashboard') }}" class="sidebar-link" @click="open=false">📊 Dashboard</a>
                        <a href="{{ url('/admin/providers') }}" class="sidebar-link" @click="open=false">🏢 Verifikasi Provider</a>
                        <a href="{{ url('/admin/refunds') }}" class="sidebar-link" @click="open=false">💸 Refund</a>
                    </nav>
                    <div class="px-3 py-4 border-t border-white/5">
                        <button @click="logout()" class="sidebar-link w-full text-red-400">🚪 Logout</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <main class="flex-1 px-4 md:px-6 py-6">
            @yield('content')
        </main>

        <footer class="text-center text-xs text-slate-400 py-4 bg-white border-t border-slate-100">
            © {{ date('Y') }} RSRV Admin Panel
        </footer>
    </div>
</div>

<script>
    function authStore() {
        return {
            token: localStorage.getItem('token'),
            user: JSON.parse(localStorage.getItem('user') || 'null'),
            init() {},
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
