@extends('layouts.customer')

@section('title', 'Detail Booking')

@section('content')
<div x-data="bookingDetail({{ $bookingId }})" x-init="load()" class="mx-auto max-w-3xl space-y-6">
    <a href="{{ url('/bookings') }}" class="inline-flex text-sm font-semibold text-blue-700 hover:underline">← Kembali ke Booking Saya</a>

    <template x-if="loading"><div class="card p-10 text-center text-sm text-slate-400">Memuat detail booking...</div></template>
    <template x-if="errorMessage" x-cloak><div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div></template>

    <template x-if="booking" x-cloak>
        <div class="space-y-5">
            <div class="card p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-mono text-xs text-slate-400" x-text="'Booking #' + booking.id"></p>
                        <h1 class="mt-1 text-2xl font-black" x-text="booking.resource?.name || 'Resource'"></h1>
                        <p class="mt-1 text-sm text-slate-500" x-text="booking.resource?.provider?.business_name || 'Provider'"></p>
                        <p class="mt-1 text-xs text-slate-400" x-text="'Kategori: ' + (booking.resource?.category?.name || 'Tanpa kategori')"></p>
                    </div>
                    <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClass(booking.status)" x-text="statusLabel(booking.status)"></span>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="font-bold">Jadwal</h2>
                <div class="mt-4 space-y-2">
                    <template x-for="slot in booking.slots || []" :key="slot.id">
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3">
                            <span class="text-sm font-semibold" x-text="slot.time_slot?.date || '-'"></span>
                            <span class="text-sm text-slate-600" x-text="(slot.time_slot?.start_time || '') + ' - ' + (slot.time_slot?.end_time || '')"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="font-bold">Pembayaran</h2>
                <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-base font-black">
                    <span>Total</span><span x-text="formatRupiah(booking.total_price)"></span>
                </div>
                <template x-if="booking.status === 'pending_payment'">
                    <div>
                        <button @click="pay()" :disabled="processing" class="mt-5 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white disabled:opacity-50">
                            <span x-text="processing ? 'Memproses...' : 'Bayar Sekarang'"></span>
                        </button>
                        <button @click="cancel()" :disabled="processing" class="mt-2 w-full rounded-xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600">Batalkan Booking</button>
                    </div>
                </template>
            </div>

            <template x-if="booking.cancellation_reason" x-cloak>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-6">
                    <h2 class="font-bold text-rose-900">Alasan Pembatalan</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-rose-800" x-text="booking.cancellation_reason"></p>
                </div>
            </template>

            <template x-if="booking.status === 'completed' && !booking.review" x-cloak>
                <div class="card p-6">
                    <h2 class="font-bold">Bagaimana pengalamanmu?</h2>
                    <p class="mt-1 text-sm text-slate-500">Berikan rating untuk membantu customer lain.</p>
                    <div class="mt-5 flex gap-2">
                        <template x-for="n in 5" :key="n">
                            <button @click="rating=n" class="text-3xl" :class="rating>=n?'text-amber-400':'text-slate-200'">★</button>
                        </template>
                    </div>
                    <textarea x-model="comment" rows="3" placeholder="Tulis pengalamanmu (opsional)..." class="mt-4 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></textarea>
                    <button @click="submitReview()" :disabled="!rating||processing" class="mt-3 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">Kirim Review</button>
                </div>
            </template>

            <template x-if="booking.review" x-cloak>
                <div class="card p-6">
                    <h2 class="font-bold">Review Kamu</h2>
                    <div class="mt-2 text-xl text-amber-400" x-text="'★'.repeat(booking.review.rating)"></div>
                    <p class="mt-2 text-sm text-slate-600" x-text="booking.review.comment || 'Tidak ada komentar.'"></p>
                </div>
            </template>
        </div>
    </template>
</div>

@push('scripts')
<script>
function bookingDetail(id){
return{
bookingId:id,booking:null,loading:true,processing:false,errorMessage:null,rating:0,comment:'',
formatRupiah(v){return'Rp '+Number(v||0).toLocaleString('id-ID')},
statusLabel(s){return{pending_payment:'Menunggu Pembayaran',confirmed:'Dikonfirmasi',completed:'Selesai',cancelled:'Dibatalkan',expired:'Expired',refunded:'Refund'}[s]||s},
statusClass(s){return{pending_payment:'bg-amber-100 text-amber-700',confirmed:'bg-blue-100 text-blue-700',completed:'bg-emerald-100 text-emerald-700',cancelled:'bg-red-100 text-red-700',expired:'bg-slate-100 text-slate-600',refunded:'bg-purple-100 text-purple-700'}[s]||'bg-slate-100 text-slate-600'},
async load(){try{this.booking=await apiFetch('/bookings/'+this.bookingId)}catch(e){if(e.status===401){window.location.href='{{ url('/login') }}';return}this.errorMessage=e.message}finally{this.loading=false}},
async pay(){this.processing=true;try{const r=await apiFetch('/bookings/'+this.bookingId+'/pay',{method:'POST'});if(r.snap_token&&window.snap)window.snap.pay(r.snap_token,{onSuccess:()=>this.load(),onPending:()=>this.load(),onError:()=>this.load()});else if(r.payment_url)window.location.href=r.payment_url}catch(e){alert(e.message)}finally{this.processing=false}},
async cancel(){
  const reason=prompt('Masukkan alasan pembatalan booking (wajib):') || '';
  if(!reason.trim()){alert('Alasan pembatalan wajib diisi.');return}
  if(!confirm('Batalkan booking dengan alasan tersebut?'))return;
  this.processing=true;
  try{
    await apiFetch('/bookings/'+this.bookingId+'/cancel',{method:'POST',body:{reason:reason.trim()}});
    await this.load()
  }catch(e){alert(e.message)}finally{this.processing=false}
},
async submitReview(){if(!this.rating)return;this.processing=true;try{await apiFetch('/bookings/'+this.bookingId+'/review',{method:'POST',body:{rating:this.rating,comment:this.comment}});await this.load()}catch(e){alert(e.message)}finally{this.processing=false}}
}}
</script>
@endpush
@endsection
