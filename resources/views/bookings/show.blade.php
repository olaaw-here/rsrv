@extends('layouts.app')

@section('title', 'Detail Booking')

@section('content')
<div x-data="bookingDetail({{ $bookingId }})" x-init="load()">

    <template x-if="booking">
        <div class="bg-white border rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold" x-text="booking.resource.name"></h1>
                    <p class="text-sm text-gray-500" x-text="booking.booking_code"></p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full" :class="statusColor(booking.status)" x-text="statusLabel(booking.status)"></span>
            </div>

            <template x-if="booking.status === 'pending_payment'" x-cloak>
                <div class="bg-yellow-50 text-yellow-700 text-sm p-3 rounded-lg mt-4">
                    Menunggu konfirmasi pembayaran... halaman ini akan otomatis update begitu pembayaran terkonfirmasi.
                    <span x-show="polling"> (mengecek ulang...)</span>
                </div>
            </template>

            <h2 class="font-semibold mt-6 mb-2">Slot yang dipesan</h2>
            <div class="space-y-1 text-sm">
                <template x-for="s in booking.slots" :key="s.time_slot_id">
                    <div class="flex justify-between border-b py-1.5">
                        <span x-text="s.slot_date + ' · ' + s.start_time + '-' + s.end_time"></span>
                        <span x-text="'Rp ' + Number(s.price_snapshot).toLocaleString('id-ID')"></span>
                    </div>
                </template>
            </div>

            <div class="flex justify-between font-bold mt-3">
                <span>Total</span>
                <span x-text="'Rp ' + Number(booking.total_price).toLocaleString('id-ID')"></span>
            </div>

            <div class="flex gap-3 mt-6">
                <template x-if="booking.status === 'pending_payment' && booking.payment && booking.payment.snap_token">
                    <button @click="pay()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Bayar Sekarang
                    </button>
                </template>

                <template x-if="booking.status === 'pending_payment'">
                    <button @click="cancel()" :disabled="cancelling"
                            class="border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 disabled:opacity-50">
                        <span x-text="cancelling ? 'Membatalkan...' : 'Batalkan Booking'"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
    function bookingDetail(bookingId) {
        return {
            bookingId,
            booking: null,
            polling: false,
            cancelling: false,
            pollTimer: null,

            async load() {
                try {
                    this.booking = await apiFetch('/bookings/' + this.bookingId);

                    // Selama masih pending_payment, polling tiap 5 detik —
                    // karena konfirmasi sesungguhnya datang async lewat webhook Midtrans.
                    if (this.booking.status === 'pending_payment') {
                        this.polling = true;
                        this.pollTimer = setTimeout(() => this.load(), 5000);
                    } else {
                        this.polling = false;
                    }
                } catch (e) {
                    if (e.status === 401) window.location.href = '{{ url('/login') }}';
                }
            },

            pay() {
                window.snap.pay(this.booking.payment.snap_token, {
                    onSuccess: () => this.load(),
                    onPending: () => this.load(),
                    onError: () => alert('Pembayaran gagal.'),
                    onClose: () => this.load(),
                });
            },

            async cancel() {
                if (!confirm('Yakin batalkan booking ini?')) return;

                this.cancelling = true;
                try {
                    this.booking = await apiFetch('/bookings/' + this.bookingId + '/cancel', { method: 'POST' });
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.cancelling = false;
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
