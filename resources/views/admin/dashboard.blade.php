&commat;extends('layouts.app')

&commat;section('title', 'Dashboard Admin')

&commat;section('content')
<div x-data="adminDashboard()" x-init="load()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Admin</h1>
            <p class="text-sm text-gray-500 mt-1">Ringkasan operasional dan aktivitas platform RSRV.</p>
        </div>
        <div class="flex items-center gap-3">
            <button &commat;click="load()" :disabled="loading" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition disabled:opacity-50" title="Muat Ulang">
                <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <a href="{{ url('/admin/refunds') }}" class="inline-flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                Kelola Refund
            </a>
        </div>
    </div>

    <!-- Error Alert -->
    <template x-if="errorMessage">
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center justify-between">
            <span class="text-sm font-medium" x-text="errorMessage"></span>
            <button &commat;click="errorMessage = ''" class="text-red-500 hover:text-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    <!-- Summary Cards Skeleton -->
    <template x-if="loading && !summary" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <template x-for="i in 4" :key="i">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm animate-pulse">
                    <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
                    <div class="h-7 bg-gray-200 rounded w-1/3"></div>
                </div>
            </template>
        </div>
    </template>

    <!-- Summary Cards Content -->
    <template x-if="summary">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Provider Pending</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-2" x-text="summary.providers_pending ?? 0"></p>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Provider Aktif</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-2" x-text="summary.providers_active ?? 0"></p>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Booking</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-2" x-text="summary.total_bookings ?? 0"></p>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Refund Menunggu</p>
                <p class="text-3xl font-extrabold text-rose-600 mt-2" x-text="summary.refunds_requested ?? 0"></p>
            </div>
        </div>
    </template>

    <!-- Bookings Table / List -->
    <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Booking Terbaru</h2>
            <span class="text-xs text-gray-500" x-text="bookings.length ? bookings.length + ' transaksi' : ''"></span>
        </div>

        <!-- Loading State for Bookings -->
        <template x-if="loading && !bookings.length" x-cloak>
            <div class="p-5 space-y-4 animate-pulse">
                <template x-for="i in 3" :key="i">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-none">
                        <div class="space-y-2 w-1/2">
                            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                            <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                        </div>
                        <div class="space-y-2 w-1/4 text-right flex flex-col items-end">
                            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!loading && bookings.length === 0" x-cloak>
            <div class="p-8 text-center">
                <p class="text-sm text-gray-500">Belum ada data booking terbaru.</p>
            </div>
        </template>

        <!-- Data List -->
        <div class="divide-y divide-gray-100" x-show="bookings.length > 0">
            <template x-for="b in bookings" :key="b.id">
                <div class="p-4 sm:px-6 hover:bg-gray-50/80 transition flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 text-sm tracking-wide" x-text="b.booking_code"></p>
                        <p class="text-xs text-gray-500 truncate mt-0.5" x-text="(b.user?.name || 'Guest') + ' · ' + (b.resource?.name || 'N/A')"></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-gray-900 text-sm" x-text="formatCurrency(b.total_price)"></p>
                        <span :class="getStatusBadgeClass(b.status)" class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium capitalize" x-text="b.status"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

&commat;push('scripts')
<script>
function adminDashboard() {
    return {
        summary: null,
        bookings: [],
        loading: false,
        errorMessage: '',

        async load() {
            this.loading = true;
            this.errorMessage = '';
            try {
                // Eksekusi API secara paralel agar response lebih cepat
                const [summaryRes, bookingsRes] = await Promise.all([
                    apiFetch('/admin/dashboard/summary'),
                    apiFetch('/admin/dashboard/bookings')
                ]);

                this.summary = summaryRes;
                this.bookings = bookingsRes.data || [];
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '{{ url('/login') }}';
                } else if (e.status === 403) {
                    this.errorMessage = 'Akses ditolak: Halaman ini khusus untuk administrator.';
                } else {
                    this.errorMessage = e.message || 'Gagal memuat data dashboard.';
                }
            } finally {
                this.loading = false;
            }
        },

        formatCurrency(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        getStatusBadgeClass(status) {
            const s = (status || '').toLowerCase();
            if (['paid', 'completed', 'success', 'approved'].includes(s)) {
                return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
            }
            if (['pending', 'waiting'].includes(s)) {
                return 'bg-amber-50 text-amber-700 border border-amber-200';
            }
            if (['cancelled', 'rejected', 'failed'].includes(s)) {
                return 'bg-rose-50 text-rose-700 border border-rose-200';
            }
            return 'bg-gray-50 text-gray-600 border border-gray-200';
        }
    }
}
</script>
&commat;endpush
&commat;endsection