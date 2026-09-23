@extends('layouts.app')

@section('title', 'Dashboard Provider')

@section('content')
<div x-data="providerDashboard()" x-init="load()">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard Provider</h1>
        <a href="{{ url('/provider/resources') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            Kelola Resource Saya
        </a>
    </div>

    <template x-if="summary">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Total Booking</p>
                <p class="text-2xl font-bold" x-text="summary.total_booking"></p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Total Pendapatan</p>
                <p class="text-2xl font-bold" x-text="'Rp ' + Number(summary.total_pendapatan).toLocaleString('id-ID')"></p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Menunggu Bayar</p>
                <p class="text-2xl font-bold" x-text="summary.pending_payment"></p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Rating</p>
                <p class="text-2xl font-bold" x-text="'⭐ ' + summary.rating_avg"></p>
            </div>
        </div>
    </template>

    <h2 class="font-semibold text-lg mb-3">Booking Terbaru</h2>

    <template x-if="loading" x-cloak><p class="text-gray-400 text-sm">Memuat...</p></template>

    <div class="bg-white border rounded-xl divide-y">
        <template x-for="b in bookings" :key="b.id">
            <div class="p-4 flex justify-between items-center">
                <div>
                    <p class="font-medium" x-text="b.resource.name + ' · ' + b.booking_code"></p>
                    <p class="text-xs text-gray-500" x-text="new Date(b.created_at).toLocaleString('id-ID')"></p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold" x-text="'Rp ' + Number(b.total_price).toLocaleString('id-ID')"></p>
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="statusColor(b.status)" x-text="b.status"></span>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function providerDashboard() {
        return {
            summary: null,
            bookings: [],
            loading: false,

            async load() {
                this.loading = true;
                try {
                    this.summary = await apiFetch('/provider/dashboard/summary');
                    const data = await apiFetch('/provider/dashboard/bookings?per_page=10');
                    this.bookings = data.data;
                } catch (e) {
                    if (e.status === 401) window.location.href = '{{ url('/login') }}';
                    if (e.status === 403) alert('Halaman ini khusus provider.');
                } finally {
                    this.loading = false;
                }
            },

            statusColor(status) {
                return {
                    pending_payment: 'bg-yellow-100 text-yellow-700',
                    confirmed: 'bg-green-100 text-green-700',
                    completed: 'bg-blue-100 text-blue-700',
                    cancelled: 'bg-red-100 text-red-700',
                    expired: 'bg-gray-100 text-gray-500',
                }[status] || 'bg-gray-100 text-gray-500';
            },
        };
    }
</script>
@endpush
@endsection
