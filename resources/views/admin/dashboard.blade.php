@extends('layouts.admin')

@section('content')

<div
    x-data="adminDashboard()"
    x-init="loadDashboard()"
    class="min-h-screen bg-slate-50"
>
    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-indigo-700">
                            Administration
                        </span>
                        <span class="text-xs text-slate-400">RSRV</span>
                    </div>

```
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Admin Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola provider, booking, dan proses refund dari satu tempat.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="loadDashboard()"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg
                        class="h-4 w-4"
                        :class="loading ? 'animate-spin' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</header>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Error --}}
    <div
        x-show="error"
        x-cloak
        class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4"
    >
        <div class="flex gap-3">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.52 20h14.96a2 2 0 001.73-3.14l-7.5-13a2 2 0 00-3.42 0z" />
                </svg>
            </div>

            <div>
                <p class="text-sm font-semibold text-red-800">
                    Gagal memuat dashboard
                </p>
                <p class="mt-1 text-sm text-red-700" x-text="error"></p>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="i in 4" :key="i">
                <div class="h-32 animate-pulse rounded-2xl bg-slate-200"></div>
            </template>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="h-64 animate-pulse rounded-2xl bg-slate-200 lg:col-span-2"></div>
            <div class="h-64 animate-pulse rounded-2xl bg-slate-200"></div>
        </div>
    </div>

    <div x-show="!loading" x-cloak>

        {{-- Statistics --}}
        <section>
            <div class="mb-4">
                <h2 class="text-base font-bold text-slate-900">
                    Overview
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Kondisi terbaru sistem RSRV.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Pending Providers --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>

                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                            Perlu Review
                        </span>
                    </div>

                    <p class="mt-5 text-sm font-medium text-slate-500">
                        Provider Pending
                    </p>

                    <p class="mt-1 text-3xl font-bold text-slate-900" x-text="stats.pending_providers">
                        —
                    </p>

                    <a
                        href="{{ url('/admin/providers') }}"
                        class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                        Review provider
                        <span>→</span>
                    </a>
                </div>

                {{-- Active Providers --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            Aktif
                        </span>
                    </div>

                    <p class="mt-5 text-sm font-medium text-slate-500">
                        Provider Aktif
                    </p>

                    <p class="mt-1 text-3xl font-bold text-slate-900" x-text="stats.active_providers">
                        —
                    </p>

                    <a
                        href="{{ url('/admin/providers') }}"
                        class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                        Kelola provider
                        <span>→</span>
                    </a>
                </div>

                {{-- Bookings --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            Total
                        </span>
                    </div>

                    <p class="mt-5 text-sm font-medium text-slate-500">
                        Total Booking
                    </p>

                    <p class="mt-1 text-3xl font-bold text-slate-900" x-text="stats.bookings">
                        —
                    </p>

                    <div class="mt-4 text-sm text-slate-500">
                        Seluruh transaksi booking
                    </div>
                </div>

                {{-- Refund --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 14l6-6m2.5-4.5A2.5 2.5 0 1114 7l-3 3a2.5 2.5 0 11-3.5-3.5L10 4m4 16l-3 3m0 0l-3-3m3 3V13" />
                            </svg>
                        </div>

                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">
                            Pending
                        </span>
                    </div>

                    <p class="mt-5 text-sm font-medium text-slate-500">
                        Refund Pending
                    </p>

                    <p class="mt-1 text-3xl font-bold text-slate-900" x-text="stats.pending_refunds">
                        —
                    </p>

                    <a
                        href="{{ url('/admin/refunds') }}"
                        class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                        Kelola refund
                        <span>→</span>
                    </a>
                </div>

            </div>
        </section>

        {{-- Main Management Area --}}
        <section class="mt-8 grid gap-6 lg:grid-cols-3">

            {{-- Management --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Management
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Akses cepat ke fungsi administrasi utama.
                    </p>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">

                    <a
                        href="{{ url('/admin/providers') }}"
                        class="group rounded-2xl border border-slate-200 p-5 transition hover:border-indigo-200 hover:bg-indigo-50/40"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H3v-2a4 4 0 014-4h4a4 4 0 014 4v2zm-2-10a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 10-2.83-4A4.01 4.01 0 0119 10z" />
                                </svg>
                            </div>

                            <span class="text-slate-300 transition group-hover:translate-x-1 group-hover:text-indigo-500">
                                →
                            </span>
                        </div>

                        <h3 class="mt-5 font-bold text-slate-900">
                            Provider Management
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Review provider baru, ubah status provider, suspend,
                            atau aktifkan kembali provider.
                        </p>

                        <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-indigo-600">
                            Buka management
                            <span>→</span>
                        </div>
                    </a>

                    <a
                        href="{{ url('/admin/refunds') }}"
                        class="group rounded-2xl border border-slate-200 p-5 transition hover:border-violet-200 hover:bg-violet-50/40"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 14l6-6m2.5-4.5A2.5 2.5 0 1114 7l-3 3a2.5 2.5 0 11-3.5-3.5L10 4m4 16l-3 3m0 0l-3-3m3 3V13" />
                                </svg>
                            </div>

                            <span class="text-slate-300 transition group-hover:translate-x-1 group-hover:text-violet-500">
                                →
                            </span>
                        </div>

                        <h3 class="mt-5 font-bold text-slate-900">
                            Refund Management
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Tinjau permintaan refund customer dan proses
                            refund sesuai status transaksi.
                        </p>

                        <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-violet-600">
                            Buka refund
                            <span>→</span>
                        </div>
                    </a>

                </div>
            </div>

            {{-- Attention --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Perlu Perhatian
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Item yang perlu ditindaklanjuti.
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.52 20h14.96a2 2 0 001.73-3.14l-7.5-13a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                </div>

                <div class="mt-6 space-y-3">

                    <a
                        href="{{ url('/admin/providers') }}"
                        class="flex items-center justify-between rounded-xl bg-slate-50 p-4 transition hover:bg-amber-50"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Provider menunggu approval
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Review pendaftaran provider
                            </p>
                        </div>

                        <span
                            class="flex h-8 min-w-8 items-center justify-center rounded-full bg-amber-100 px-2 text-xs font-bold text-amber-700"
                            x-text="stats.pending_providers"
                        >
                            —
                        </span>
                    </a>

                    <a
                        href="{{ url('/admin/refunds') }}"
                        class="flex items-center justify-between rounded-xl bg-slate-50 p-4 transition hover:bg-violet-50"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Refund menunggu proses
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Periksa permintaan customer
                            </p>
                        </div>

                        <span
                            class="flex h-8 min-w-8 items-center justify-center rounded-full bg-violet-100 px-2 text-xs font-bold text-violet-700"
                            x-text="stats.pending_refunds"
                        >
                            —
                        </span>
                    </a>

                </div>

                <div
                    x-show="Number(stats.pending_providers) === 0 && Number(stats.pending_refunds) === 0"
                    class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4"
                >
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>

                        <div>
                            <p class="text-sm font-semibold text-emerald-800">
                                Semua aman
                            </p>
                            <p class="mt-1 text-xs leading-5 text-emerald-700">
                                Tidak ada provider atau refund yang membutuhkan
                                tindakan saat ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        {{-- System Summary --}}
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        RSRV Administration
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Panel administrasi untuk menjaga operasional platform tetap terkontrol.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Dashboard connected
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Provider
                    </p>
                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        Approval & status
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Kontrol akses provider terhadap platform.
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Booking
                    </p>
                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        Monitoring transaksi
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Pantau aktivitas booking yang terjadi di RSRV.
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Finance
                    </p>
                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        Refund management
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Kelola permintaan pengembalian dana customer.
                    </p>
                </div>

            </div>
        </section>

    </div>
</main>
```

</div>

<script>
function adminDashboard() {
    return {
        loading: true,

        error: '',

        stats: {
            pending_providers: 0,
            active_providers: 0,
            bookings: 0,
            pending_refunds: 0,
        },

        async loadDashboard() {
            this.loading = true;
            this.error = '';

            try {
                const token = localStorage.getItem('token') || '';

                if (!token) {
                    throw new Error('Sesi login tidak ditemukan. Silakan login kembali.');
                }

                const response = await fetch('/api/admin/dashboard', {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${token}`,
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        'Tidak dapat mengambil data dashboard.'
                    );
                }

                const payload = data.data || data.stats || data;

                this.stats.pending_providers =
                    Number(
                        payload.pending_providers ??
                        payload.stats?.pending_providers ??
                        0
                    );

                this.stats.active_providers =
                    Number(
                        payload.active_providers ??
                        payload.stats?.active_providers ??
                        0
                    );

                this.stats.bookings =
                    Number(
                        payload.bookings ??
                        payload.total_bookings ??
                        payload.stats?.bookings ??
                        0
                    );

                this.stats.pending_refunds =
                    Number(
                        payload.pending_refunds ??
                        payload.stats?.pending_refunds ??
                        0
                    );

            } catch (error) {
                console.error('Admin dashboard error:', error);

                this.error = error.message ||
                    'Terjadi kesalahan saat mengambil data dashboard.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>

@endsection
