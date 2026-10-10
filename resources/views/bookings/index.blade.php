@extends('layouts.customer')

@section('title', 'Booking Saya')

@section('content')
<div x-data="bookingList()" x-init="load()" class="space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Customer</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight">Booking Saya</h1>
        <p class="mt-2 text-sm text-slate-500">Pantau jadwal, pembayaran, dan status booking kamu.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="tab in tabs" :key="tab.value">
            <button @click="status = tab.value; load()"
                    class="rounded-full px-4 py-2 text-xs font-bold"
                    :class="status === tab.value ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600'">
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    <template x-if="errorMessage" x-cloak>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div>
    </template>

    <template x-if="loading">
        <div class="card p-10 text-center text-sm text-slate-400">Memuat booking...</div>
    </template>

    <template x-if="!loading && bookings.length === 0" x-cloak>
        <div class="card p-12 text-center">
            <div class="text-4xl">📅</div>
            <h2 class="mt-3 font-bold">Belum ada booking</h2>
            <p class="mt-1 text-sm text-slate-500">Booking yang kamu buat akan muncul di sini.</p>
            <a href="{{ url('/resources') }}" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Cari Resource</a>
        </div>
    </template>

    <div class="space-y-4">
        <template x-for="booking in bookings" :key="booking.id">
            <article class="card p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-slate-400" x-text="'#' + booking.id"></span>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="statusClass(booking.status)" x-text="statusLabel(booking.status)"></span>
                        </div>
                        <h2 class="mt-2 text-lg font-bold" x-text="booking.resource?.name || 'Resource'"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="bookingDate(booking)"></p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs text-slate-400">Total</p>
                        <p class="text-lg font-black" x-text="formatRupiah(booking.total_price)"></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a :href="'{{ url('/bookings') }}/' + booking.id" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold">Lihat Detail</a>
                    <button x-show="booking.status === 'pending_payment'" @click="pay(booking)" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">Bayar Sekarang</button>
                    <button x-show="booking.status === 'pending_payment'" @click="cancel(booking)" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600">Batalkan</button>
                    <a x-show="booking.status === 'completed' && !booking.review" :href="'{{ url('/bookings') }}/' + booking.id" class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-white">Beri Review</a>
                </div>
            </article>
        </template>
    </div>

    <template x-if="meta.last_page > 1">
        <div class="flex justify-center gap-3">
            <button @click="load(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="rounded-xl border px-4 py-2 disabled:opacity-40">←</button>
            <span class="flex items-center text-sm text-slate-500" x-text="meta.current_page + ' / ' + meta.last_page"></span>
            <button @click="load(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="rounded-xl border px-4 py-2 disabled:opacity-40">→</button>
        </div>
    </template>
</div>

@push('scripts')
<script>
function bookingList() {
    return {
        bookings: [], loading: false, errorMessage: null, status: '',
        tabs: [
            {value:'',label:'Semua'}, {value:'pending_payment',label:'Menunggu Pembayaran'},
            {value:'confirmed',label:'Dikonfirmasi'}, {value:'completed',label:'Selesai'},
            {value:'cancelled',label:'Dibatalkan'}, {value:'refunded',label:'Refund'}
        ],
        meta: {current_page:1,last_page:1},

        formatRupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); },
        statusLabel(s) { return {pending_payment:'Menunggu Pembayaran',confirmed:'Dikonfirmasi',completed:'Selesai',cancelled:'Dibatalkan',expired:'Expired',refunded:'Refund'}[s] || s; },
        statusClass(s) { return {pending_payment:'bg-amber-100 text-amber-700',confirmed:'bg-blue-100 text-blue-700',completed:'bg-emerald-100 text-emerald-700',cancelled:'bg-red-100 text-red-700',expired:'bg-slate-100 text-slate-600',refunded:'bg-purple-100 text-purple-700'}[s] || 'bg-slate-100 text-slate-600'; },
        bookingDate(b) {
            const s = (b.booking_slots || [])[0]?.time_slot || {};
            return s.date ? `${s.date} · ${s.start_time || ''} - ${s.end_time || ''}` : 'Jadwal belum tersedia';
        },
        async load(page=1) {
            this.loading=true; this.errorMessage=null;
            try {
                const p=new URLSearchParams({page,per_page:10});
                if(this.status)p.set('status',this.status);
                const d=await apiFetch('/bookings?'+p.toString());
                this.bookings=d.data||[]; this.meta=d.meta||this.meta;
            } catch(e) {
                if(e.status===401){window.location.href='{{ url('/login') }}';return;}
                this.errorMessage=e.message;
            } finally {this.loading=false;}
        },
        async pay(b) {
            try {
                const r=await apiFetch('/bookings/'+b.id+'/pay',{method:'POST'});
                if(r.snap_token&&window.snap) window.snap.pay(r.snap_token,{onSuccess:()=>this.load(),onPending:()=>this.load(),onError:()=>this.load()});
                else if(r.payment_url) window.location.href=r.payment_url;
            } catch(e){alert(e.message);}
        },
        async cancel(b) {
            // API mewajibkan alasan pembatalan.
            const reason = prompt('Alasan pembatalan booking ini:');
            if (reason === null) return;
            if (!reason.trim()) { alert('Alasan pembatalan wajib diisi.'); return; }
            try {await apiFetch('/bookings/'+b.id+'/cancel',{method:'POST',body:{reason:reason.trim()}});await this.load();}
            catch(e){alert(e.data?.errors ? Object.values(e.data.errors).flat().join('\n') : e.message);}
        }
    };
}
</script>
@endpush
@endsection
