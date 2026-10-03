@extends('layouts.provider')

@section('content')

<div class="min-h-screen bg-slate-50" x-data="providerBookingsPage()" x-init="init()">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Workspace Provider</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">Booking Masuk</h1>
                <p class="mt-2 text-sm text-slate-600">Kelola booking, konfirmasi pesanan, dan tandai layanan yang sudah selesai.</p>
            </div>
                <button @click="loadBookings()" :disabled="loading" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50" x-text="loading ? 'Memuat...' : 'Muat ulang'"></button>
        </div>

        <template x-if="error">
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" x-text="error"></div>
        </template>

        <template x-if="notice">
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700" x-text="notice"></div>
        </template>

        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <template x-for="s in stats" :key="s.key">
                <button @click="activeFilter=s.key;loadBookings()" class="rounded-2xl border bg-white p-4 text-left shadow-sm" :class="activeFilter===s.key?'border-indigo-300 ring-2 ring-indigo-100':'border-slate-200'">
                    <p class="text-xs text-slate-500" x-text="s.label"></p>
                    <p class="mt-2 text-2xl font-bold" x-text="s.value"></p>
                </button>
            </template>
        </div>

        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Cari booking</label>
                <input x-model="search" @input.debounce.300ms="loadBookings()" type="search" placeholder="Nama customer, resource, atau kode booking" class="w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div class="sm:w-56">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
                <select x-model="activeFilter" @change="loadBookings()" class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="all">Semua status</option>
                    <option value="pending_payment">Menunggu pembayaran</option>
                    <option value="confirmed">Terkonfirmasi</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                    <option value="expired">Kedaluwarsa</option>
                </select>
            </div>
        </div>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">Daftar pesanan</h2>
                <span class="text-xs text-slate-500" x-text="`${filteredBookings.length} booking`"></span>
            </div>
            <template x-if="loading && bookings.length===0">
                <div class="p-10 text-center text-sm text-slate-500">Memuat data booking...</div>
            </template>
            <template x-if="!loading && filteredBookings.length===0">
                <div class="p-10 text-center">
                    <p class="font-semibold">Belum ada booking yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter atau muat ulang data.</p>
                </div>
            </template>

        <div class="divide-y divide-slate-100">
            <template x-for="b in filteredBookings" :key="b.id">
                <article class="p-5 hover:bg-slate-50/70">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900" x-text="b.resource?.name || b.resource_name || 'Resource'"></p>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(b.status)" x-text="statusLabel(b.status)"></span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">Customer: 
                                <span class="font-medium" x-text="b.user?.name || b.customer?.name || b.customer_name || 'Customer'"></span>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span x-text="`Kode: #${b.booking_code || b.code || b.id}`"></span>
                                <span x-text="`Dibuat: ${formatDate(b.created_at)}`"></span>
                                <span x-text="`Total: ${formatMoney(b.total_amount ?? b.total_price ?? b.amount ?? 0)}`"></span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button @click="showDetails(b)" class="rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-semibold">Detail</button>
                            <button x-show="b.status==='confirmed'" @click="performAction(b,'complete')" class="rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white">Tandai selesai</button>
                            <button x-show="b.status==='pending_payment' && b.payment_status==='settlement'" @click="performAction(b,'confirm')" class="rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white">Konfirmasi</button>
                            <button x-show="b.status==='pending_payment' && b.payment_status!=='settlement'" @click="performAction(b,'reject')" class="rounded-xl border border-rose-200 px-3.5 py-2 text-sm font-semibold text-rose-700">Tolak</button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>
        <div x-show="selectedBooking" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="selectedBooking=null">
            <div @click.outside="selectedBooking=null" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex justify-between gap-4"><div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Detail booking</p>
                    <h2 class="mt-1 text-xl font-bold" x-text="selectedBooking?.resource?.name || selectedBooking?.resource_name || 'Pesanan'"></h2>
                    <p class="mt-1 text-sm text-slate-500" x-text="`Kode #${selectedBooking?.booking_code || selectedBooking?.code || selectedBooking?.id || ''}`"></p>
                </div>
                <button @click="selectedBooking=null" class="rounded-lg p-2 hover:bg-slate-100">✕</button>
            </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Customer</p>
                        <p class="mt-1 font-semibold" x-text="selectedBooking?.user?.name || selectedBooking?.customer?.name || selectedBooking?.customer_name || '—'"></p>
                        <p class="mt-1 break-all text-sm text-slate-600" x-text="selectedBooking?.user?.email || selectedBooking?.customer?.email || ''"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Total pembayaran</p>
                        <p class="mt-1 text-xl font-bold" x-text="formatMoney(selectedBooking?.total_amount ?? selectedBooking?.total_price ?? selectedBooking?.amount ?? 0)"></p>
                        <p class="mt-1 text-sm text-slate-600" x-text="`Status: ${statusLabel(selectedBooking?.status)}`"></p>
                    </div>
                </div>
                    <div class="mt-5 rounded-2xl border border-slate-200 p-4">
                        <h3 class="font-semibold">Jadwal</h3>
                        <template x-if="selectedBooking?.booking_slots?.length">
                            <div class="mt-3 space-y-2">
                                <template x-for="slot in selectedBooking.booking_slots" :key="slot.id">
                                    <div class="flex justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                        <span x-text="formatDate(slot.time_slot?.date || slot.date || slot.timeSlot?.date)"></span>
                                        <span x-text="`${slot.time_slot?.start_time || slot.timeSlot?.start_time || ''} – ${slot.time_slot?.end_time || slot.timeSlot?.end_time || ''}`"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <p x-show="!selectedBooking?.booking_slots?.length" class="mt-2 text-sm text-slate-500">Rincian slot tidak tersedia dari respons API.</p>
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs text-slate-500">Catatan customer</p>
                            <p class="mt-1 whitespace-pre-line text-sm" x-text="selectedBooking?.notes || selectedBooking?.customer_note || 'Tidak ada catatan.'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function providerBookingsPage(){return {
 bookings:[],loading:false,error:'',notice:'',search:'',activeFilter:'all',selectedBooking:null,stats:[],workingId:null,
 async init(){await this.loadBookings()},
 async api(url,options={}){const 
 token=localStorage.getItem('token')||'';const r=await fetch(url,{...options,headers:{'Accept':'application/json','Content-Type':'application/json',...(token?{'Authorization':`Bearer ${token}`} : {}),...(options.headers||{})}});const d=await r.json().catch(()=>({}));if(!r.ok)throw new Error(d.message||'Permintaan gagal. Periksa login dan endpoint API.');return d},
 async loadBookings(){this.loading=true;this.error='';try{const p=new URLSearchParams();if(this.activeFilter!=='all')p.set('status',this.activeFilter);if(this.search.trim())p.set('search',this.search.trim());const r=await this.api('/api/provider/bookings'+(p.toString()?'?'+p:''));const d=Array.isArray(r)?r:(r.data||r.bookings||[]);this.bookings=Array.isArray(d)?d:(d.data||[]);this.stats=[{key:'all',label:'Semua booking',value:this.bookings.length},{key:'pending_payment',label:'Menunggu pembayaran',value:this.bookings.filter(x=>x.status==='pending_payment').length},{key:'confirmed',label:'Terkonfirmasi',value:this.bookings.filter(x=>x.status==='confirmed').length},{key:'completed',label:'Selesai',value:this.bookings.filter(x=>x.status==='completed').length}]}catch(e){this.error=e.message}finally{this.loading=false}},
 get filteredBookings(){const q=this.search.trim().toLowerCase();return this.bookings.filter(b=>(this.activeFilter==='all'||b.status===this.activeFilter)&&(!q||[b.booking_code,b.code,b.id,b.resource?.name,b.resource_name,b.user?.name,b.customer?.name,b.customer_name].join(' ').toLowerCase().includes(q)))},
 async showDetails(b){this.selectedBooking=b;try{const r=await this.api(`/api/provider/bookings/${b.id}`);this.selectedBooking=r.data||r.booking||r}catch(e){}},
 async performAction(b,a){const label={confirm:'mengonfirmasi',complete:'menandai selesai',reject:'menolak'}[a];if(!confirm(`Yakin ingin ${label} booking ini?`))return;this.workingId=b.id;this.error='';this.notice='';try{const r=await this.api(`/api/provider/bookings/${b.id}/${a}`,{method:'POST',body:JSON.stringify({})});this.notice=r.message||'Perubahan booking berhasil disimpan.';this.selectedBooking=null;await this.loadBookings()}catch(e){this.error=e.message}finally{this.workingId=null}},
 statusLabel(s){return ({pending_payment:'Menunggu pembayaran',confirmed:'Terkonfirmasi',completed:'Selesai',cancelled:'Dibatalkan',expired:'Kedaluwarsa',refunded:'Refund'})[s]||s||'—'},
 statusClass(s){return ({pending_payment:'bg-amber-50 text-amber-700',confirmed:'bg-indigo-50 text-indigo-700',completed:'bg-emerald-50 text-emerald-700',cancelled:'bg-rose-50 text-rose-700',expired:'bg-slate-100 text-slate-600',refunded:'bg-violet-50 text-violet-700'})[s]||'bg-slate-100 text-slate-600'},
 formatMoney(v){return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Number(v||0))},
 formatDate(v){if(!v)return'—';const d=new Date(v);return Number.isNaN(d.getTime())?v:new Intl.DateTimeFormat('id-ID',{dateStyle:'medium'}).format(d)}
}}
</script>
@endsection
