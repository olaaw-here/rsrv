@extends('layouts.app')

@section('title', 'Booking Saya')

@section('content')
<div x-data="bookingList()" x-init="load()">

    <h1 class="text-2xl font-bold mb-4">Booking Saya</h1>

    <div class="flex gap-2 mb-4 text-sm">
        <template x-for="s in statuses" :key="s.value">
            <button @click="filterStatus = s.value; load()"
                    :class="filterStatus === s.value ? 'bg-blue-600 text-white' : 'bg-white border'"
                    class="px-3 py-1.5 rounded-full" x-text="s.label"></button>
        </template>
    </div>

    <template x-if="loading" x-cloak><p class="text-gray-400 text-sm">Memuat...</p></template>
    <template x-if="!loading && items.length === 0" x-cloak><p class="text-gray-400 text-sm">Belum ada booking.</p></template>

    <div class="space-y-3">
        <template x-for="b in items" :key="b.id">
            <a :href="'{{ url('/bookings') }}/' + b.id" class="block bg-white border rounded-xl p-4 hover:shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold" x-text="b.resource.name"></p>
                        <p class="text-xs text-gray-500" x-text="b.booking_code"></p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full"
                          :class="statusColor(b.status)" x-text="statusLabel(b.status)"></span>
                </div>
                <p class="text-sm mt-2 font-medium" x-text="'Rp ' + Number(b.total_price).toLocaleString('id-ID')"></p>
            </a>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function bookingList() {
        return {
            items: [],
            loading: false,
            filterStatus: '',
            statuses: [
                { value: '', label: 'Semua' },
                { value: 'pending_payment', label: 'Menunggu Bayar' },
                { value: 'confirmed', label: 'Terkonfirmasi' },
                { value: 'completed', label: 'Selesai' },
                { value: 'cancelled', label: 'Dibatalkan' },
            ],

            async load() {
                this.loading = true;
                try {
                    const params = this.filterStatus ? ('?status=' + this.filterStatus) : '';
                    const data = await apiFetch('/bookings' + params);
                    this.items = data.data;
                } catch (e) {
                    if (e.status === 401) window.location.href = '{{ url('/login') }}';
                } finally {
                    this.loading = false;
                }
            },

            statusLabel(status) {
                return { pending_payment: 'Menunggu Bayar', confirmed: 'Terkonfirmasi', completed: 'Selesai',
                    cancelled: 'Dibatalkan', expired: 'Kedaluwarsa', refunded: 'Direfund' }[status] || status;
            },
            statusColor(status) {
                return {
                    pending_payment: 'bg-yellow-100 text-yellow-700',
                    confirmed: 'bg-green-100 text-green-700',
                    completed: 'bg-blue-100 text-blue-700',
                    cancelled: 'bg-red-100 text-red-700',
                    expired: 'bg-gray-100 text-gray-500',
                    refunded: 'bg-purple-100 text-purple-700',
                }[status] || 'bg-gray-100 text-gray-500';
            },
        };
    }
</script>
@endpush
@endsection
