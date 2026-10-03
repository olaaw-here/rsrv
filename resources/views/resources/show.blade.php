@extends('layouts.customer')

@section('title', 'Detail Resource')

@section('content')
<div x-data="resourceDetail({{ $resourceId }})" x-init="load()" class="space-y-6">

    <a href="{{ url('/resources') }}" class="inline-flex text-sm font-semibold text-blue-700 hover:underline">
        ← Kembali ke katalog
    </a>

    <template x-if="loadingResource" x-cloak>
        <div class="card p-10 text-center text-sm text-slate-400">Memuat detail resource...</div>
    </template>

    <template x-if="errorMessage" x-cloak>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div>
    </template>

    <template x-if="resource" x-cloak>
        <div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">

            <div class="space-y-5">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <div class="flex h-64 items-center justify-center bg-slate-100 sm:h-80">
                        <template x-if="resource.images && resource.images.length">
                            <img :src="resource.images[0]" :alt="resource.name" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!resource.images || !resource.images.length">
                            <span class="text-6xl" x-text="typeIcon(resource.type)"></span>
                        </template>
                    </div>

                    <div class="p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase text-blue-700" x-text="typeLabel(resource.type)"></span>
                            <span class="text-sm text-amber-600" x-text="'★ ' + Number(resource.provider?.rating_avg || 0).toFixed(1)"></span>
                        </div>

                        <h1 class="mt-3 text-3xl font-black tracking-tight" x-text="resource.name"></h1>
                        <p class="mt-2 text-sm text-slate-500"
                           x-text="(resource.provider?.business_name || 'Provider') + ' · ' + (resource.provider?.city || '-')"></p>
                        <p class="mt-5 whitespace-pre-line text-sm leading-6 text-slate-600"
                           x-text="resource.description || 'Provider belum menambahkan deskripsi.'"></p>

                        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-400">Harga / slot</p>
                                <p class="mt-1 font-bold" x-text="formatRupiah(resource.base_price)"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-400">Durasi</p>
                                <p class="mt-1 font-bold" x-text="resource.slot_duration_minutes + ' menit'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-400">Kapasitas</p>
                                <p class="mt-1 font-bold" x-text="resource.capacity || '-'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:sticky lg:top-24 lg:self-start">
                <div class="card p-5">
                    <h2 class="text-lg font-bold">Pilih jadwal</h2>
                    <p class="mt-1 text-sm text-slate-500">Slot yang tersedia dapat langsung dipilih.</p>

                    <label class="mt-5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Tanggal
                        <input type="date" x-model="selectedDate" @change="loadSlots()"
                               :min="minDate"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal outline-none focus:border-blue-500">
                    </label>

                    <template x-if="loadingSlots">
                        <div class="py-8 text-center text-sm text-slate-400">Memuat slot...</div>
                    </template>

                    <template x-if="!loadingSlots && slots.length === 0">
                        <div class="mt-5 rounded-xl bg-slate-50 p-5 text-center">
                            <p class="text-sm font-semibold text-slate-600">Belum ada slot tersedia</p>
                            <p class="mt-1 text-xs text-slate-400">Coba pilih tanggal lainnya.</p>
                        </div>
                    </template>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <template x-for="slot in slots" :key="slot.id">
                            <button @click="toggleSlot(slot)"
                                    :disabled="slot.status !== 'available'"
                                    :class="slotClass(slot)"
                                    class="rounded-xl border px-2 py-3 text-center transition">
                                <span class="block text-xs font-bold" x-text="slot.start_time + ' – ' + slot.end_time"></span>
                                <span class="mt-1 block text-[11px]" x-text="slot.status === 'available' ? formatRupiah(slot.price) : statusLabel(slot.status)"></span>
                            </button>
                        </template>
                    </div>

                    <template x-if="selectedSlots.length > 0" x-cloak>
                        <div class="mt-5 border-t border-slate-100 pt-5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500" x-text="selectedSlots.length + ' slot dipilih'"></span>
                                <span class="text-xl font-black" x-text="formatRupiah(totalPrice)"></span>
                            </div>

                            <button @click="bookNow()" :disabled="booking"
                                    class="mt-4 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                                <span x-text="booking ? 'Membuat booking...' : 'Booking & Bayar'"></span>
                            </button>

                            <p class="mt-2 text-center text-[11px] text-slate-400">Pembayaran diproses melalui Midtrans.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function resourceDetail(resourceId) {
    return {
        resourceId,
        resource: null,
        loadingResource: true,
        errorMessage: null,
        selectedDate: null,
        minDate: null,
        slots: [],
        selectedSlots: [],
        loadingSlots: false,
        booking: false,

        typeLabel(type) {
            return { tempat: 'Tempat', lapangan: 'Lapangan', konsultasi: 'Konsultasi' }[type] || type;
        },

        typeIcon(type) {
            return { tempat: '🏢', lapangan: '⚽', konsultasi: '💬' }[type] || '📍';
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        statusLabel(status) {
            return { booked: 'Terisi', blocked: 'Ditutup' }[status] || status;
        },

        slotClass(slot) {
            if (this.isSelected(slot)) return 'border-blue-600 bg-blue-600 text-white';
            if (slot.status === 'available') return 'border-slate-200 bg-white hover:border-blue-400 hover:bg-blue-50';
            return 'cursor-not-allowed border-slate-100 bg-slate-100 text-slate-400';
        },

        async load() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            const localDate = date => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            this.minDate = localDate(tomorrow);
            this.selectedDate = this.minDate;

            try {
                this.resource = await apiFetch('/resources/' + this.resourceId);
                await this.loadSlots();
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.loadingResource = false;
            }
        },

        async loadSlots() {
            this.loadingSlots = true;
            this.selectedSlots = [];

            try {
                this.slots = await apiFetch('/resources/' + this.resourceId + '/slots?date=' + this.selectedDate);
            } catch (e) {
                this.slots = [];
                this.errorMessage = e.message;
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
            return this.selectedSlots.reduce((sum, slot) => sum + Number(slot.price || 0), 0);
        },

        async bookNow() {
            if (!localStorage.getItem('token')) {
                window.location.href = '{{ url('/login') }}';
                return;
            }

            if (!this.selectedSlots.length) return;

            this.booking = true;

            try {
                const result = await apiFetch('/bookings', {
                    method: 'POST',
                    body: {
                        resource_id: this.resourceId,
                        time_slot_ids: this.selectedSlots.map(s => s.id),
                    },
                });

                if (result.snap_token && window.snap) {
                    window.snap.pay(result.snap_token, {
                        onSuccess: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                        onPending: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                        onError: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                        onClose: () => window.location.href = '{{ url('/bookings') }}/' + result.booking.id,
                    });
                } else {
                    window.location.href = '{{ url('/bookings') }}/' + result.booking.id;
                }
            } catch (e) {
                if (e.status === 409) {
                    alert('Slot baru saja diambil customer lain. Silakan pilih slot lain.');
                    await this.loadSlots();
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
