@extends('layouts.app')

@section('title', 'Detail')

@section('content')
<div x-data="resourceDetail({{ $resourceId }})" x-init="load()">

    <template x-if="resource">
        <div>
            <span class="text-xs uppercase tracking-wide text-blue-600 font-semibold" x-text="resource.type"></span>
            <h1 class="text-2xl font-bold mt-1" x-text="resource.name"></h1>
            <p class="text-gray-500 text-sm mt-1"
               x-text="resource.provider.business_name + ' · ' + (resource.provider.city || '-') + ' · ⭐ ' + resource.provider.rating_avg"></p>
            <p class="text-gray-700 mt-3" x-text="resource.description"></p>

            <hr class="my-6">

            <h2 class="font-semibold text-lg mb-3">Pilih Tanggal & Slot Waktu</h2>

            <input type="date" x-model="selectedDate" @change="loadSlots()"
                   :min="minDate" class="border rounded-lg px-3 py-2 mb-4">

            <template x-if="loadingSlots" x-cloak>
                <p class="text-gray-400 text-sm">Memuat slot...</p>
            </template>

            <template x-if="!loadingSlots && slots.length === 0" x-cloak>
                <p class="text-gray-400 text-sm">Tidak ada slot untuk tanggal ini. Coba tanggal lain.</p>
            </template>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <template x-for="slot in slots" :key="slot.id">
                    <button
                        @click="toggleSlot(slot)"
                        :disabled="slot.status !== 'available'"
                        :class="{
                            'bg-blue-600 text-white border-blue-600': isSelected(slot),
                            'bg-white hover:border-blue-400': !isSelected(slot) && slot.status === 'available',
                            'bg-gray-100 text-gray-400 cursor-not-allowed': slot.status !== 'available',
                        }"
                        class="border rounded-lg px-2 py-2 text-sm">
                        <span x-text="slot.start_time + ' - ' + slot.end_time"></span>
                        <template x-if="slot.status === 'booked'"><div class="text-[10px]">Terisi</div></template>
                        <template x-if="slot.status === 'blocked'"><div class="text-[10px]">Ditutup</div></template>
                    </button>
                </template>
            </div>

            <!-- Ringkasan & tombol booking -->
            <template x-if="selectedSlots.length > 0" x-cloak>
                <div class="sticky bottom-0 mt-6 bg-white border rounded-xl p-4 shadow-lg flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500" x-text="selectedSlots.length + ' slot dipilih'"></p>
                        <p class="font-bold text-lg" x-text="'Rp ' + totalPrice.toLocaleString('id-ID')"></p>
                    </div>
                    <button @click="bookNow()" :disabled="booking"
                            class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-text="booking ? 'Memproses...' : 'Booking & Bayar'"></span>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>

@push('scripts')
<script>
    function resourceDetail(resourceId) {
        return {
            resourceId,
            resource: null,
            selectedDate: null,
            minDate: null,
            slots: [],
            selectedSlots: [],
            loadingSlots: false,
            booking: false,

            async load() {
                if (!localStorage.getItem('token')) {
                    // Boleh lihat detail tanpa login, tapi booking wajib login.
                }

                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                this.minDate = tomorrow.toISOString().slice(0, 10);
                this.selectedDate = this.minDate;

                try {
                    this.resource = await apiFetch('/resources/' + this.resourceId);
                    await this.loadSlots();
                } catch (e) {
                    alert(e.message);
                }
            },

            async loadSlots() {
                this.loadingSlots = true;
                this.selectedSlots = [];
                try {
                    this.slots = await apiFetch('/resources/' + this.resourceId + '/slots?date=' + this.selectedDate);
                } catch (e) {
                    this.slots = [];
                } finally {
                    this.loadingSlots = false;
                }
            },

            isSelected(slot) {
                return this.selectedSlots.some(s => s.id === slot.id);
            },

            toggleSlot(slot) {
                if (slot.status !== 'available') return;

                if (this.isSelected(slot)) {
                    this.selectedSlots = this.selectedSlots.filter(s => s.id !== slot.id);
                } else {
                    this.selectedSlots.push(slot);
                }
            },

            get totalPrice() {
                return this.selectedSlots.reduce((sum, s) => sum + Number(s.price), 0);
            },

            async bookNow() {
                if (!localStorage.getItem('token')) {
                    window.location.href = '{{ url('/login') }}';
                    return;
                }

                this.booking = true;

                try {
                    const result = await apiFetch('/bookings', {
                        method: 'POST',
                        body: {
                            resource_id: this.resourceId,
                            time_slot_ids: this.selectedSlots.map(s => s.id),
                        },
                    });

                    // Buka popup pembayaran Midtrans Snap.
                    window.snap.pay(result.snap_token, {
                        onSuccess: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                        onPending: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                        onError: () => alert('Pembayaran gagal, silakan coba lagi.'),
                        onClose: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                    });
                } catch (e) {
                    // 409 = slot sudah diambil orang lain (race condition tertangkap di backend)
                    if (e.status === 409) {
                        alert('Yah, slot yang dipilih baru saja diambil orang lain. Silakan pilih slot lain.');
                        this.loadSlots();
                    } else {
                        alert(e.message);
                    }
                } finally {
                    this.booking = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
