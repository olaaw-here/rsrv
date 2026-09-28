@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div x-data="adminDashboard()" x-init="load()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Dashboard Admin</h1>
            <p class="text-sm text-gray-500">Ringkasan operasional RSRV.</p>
        </div>
        <a href="{{ url('/admin/refunds') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm">Kelola Refund</a>
    </div>

    <template x-if="loading" x-cloak><p class="text-gray-400">Memuat...</p></template>

    <template x-if="summary">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Provider Pending</p><p class="text-2xl font-bold" x-text="summary.providers_pending"></p></div>
            <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Provider Aktif</p><p class="text-2xl font-bold" x-text="summary.providers_active"></p></div>
            <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Total Booking</p><p class="text-2xl font-bold" x-text="summary.total_bookings"></p></div>
            <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Refund Menunggu</p><p class="text-2xl font-bold" x-text="summary.refunds_requested"></p></div>
        </div>
    </template>

    <div class="bg-white border rounded-xl p-5">
        <h2 class="font-semibold mb-4">Booking Terbaru</h2>
        <div class="divide-y">
            <template x-for="b in bookings" :key="b.id">
                <div class="py-3 flex justify-between gap-4">
                    <div><p class="font-medium" x-text="b.booking_code"></p><p class="text-xs text-gray-500" x-text="b.user?.name + ' · ' + b.resource?.name"></p></div>
                    <div class="text-right"><p class="font-medium" x-text="'Rp ' + Number(b.total_price).toLocaleString('id-ID')"></p><span class="text-xs" x-text="b.status"></span></div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function adminDashboard() {
    return {
        summary: null, bookings: [], loading: false,
        async load() {
            this.loading = true;
            try {
                this.summary = await apiFetch('/admin/dashboard/summary');
                const result = await apiFetch('/admin/dashboard/bookings');
                this.bookings = result.data || [];
            } catch (e) {
                if (e.status === 401) window.location.href = '{{ url('/login') }}';
                else if (e.status === 403) alert('Halaman ini khusus admin.');
                else alert(e.message);
            } finally { this.loading = false; }
        }
    }
}
</script>
@endpush
@endsection
