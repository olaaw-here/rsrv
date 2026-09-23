<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Booking System')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- Midtrans Snap.js (sandbox). Ganti ke app.midtrans.com untuk production. --}}
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col" x-data="authStore()" x-init="init()">

    <nav class="bg-white border-b sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/resources') }}" class="font-bold text-lg">🗓️ BookingApp</a>

            <div class="flex items-center gap-4 text-sm">
                <a href="{{ url('/resources') }}" class="hover:text-blue-600">Cari Tempat/Jasa</a>

                <template x-if="token">
                    <div class="flex items-center gap-4">
                        <a href="{{ url('/bookings') }}" class="hover:text-blue-600">Booking Saya</a>
                        <template x-if="user && user.role === 'provider'">
                            <a href="{{ url('/provider/dashboard') }}" class="hover:text-blue-600">Dashboard Provider</a>
                        </template>
                        <span class="text-gray-400" x-text="user ? user.name : ''"></span>
                        <button @click="logout()" class="text-red-600 hover:underline">Logout</button>
                    </div>
                </template>

                <template x-if="!token">
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/login') }}" class="hover:text-blue-600">Login</a>
                        <a href="{{ url('/register') }}" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">Daftar</a>
                    </div>
                </template>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto w-full px-4 py-6 flex-1">
        @yield('content')
    </main>

    <footer class="text-center text-xs text-gray-400 py-6">
        Marketplace Jasa / Booking System — Demo Frontend (Blade + Alpine.js)
    </footer>

    <script>
        // ---------------------------------------------------------------
        // Alpine store global untuk status login (token disimpan di localStorage)
        // ---------------------------------------------------------------
        function authStore() {
            return {
                token: localStorage.getItem('token'),
                user: JSON.parse(localStorage.getItem('user') || 'null'),
                init() {
                    // no-op, token & user sudah di-load dari localStorage di atas
                },
                setAuth(token, user) {
                    this.token = token;
                    this.user = user;
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

        // ---------------------------------------------------------------
        // Helper fetch ke API — otomatis kirim Bearer token & handle JSON.
        // Dipakai di semua halaman lewat window.apiFetch(...)
        // ---------------------------------------------------------------
        window.apiFetch = async function (path, options = {}) {
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
                error.status = res.status;
                error.data = data;
                throw error;
            }

            return data;
        };
    </script>

    @stack('scripts')
</body>
</html>
