@extends('layouts.app')
@section('title', 'Booking Provider')
@section('content')
<div x-data="providerBookings()" x-init="load()">
    <div class="flex justify-between items-center mb-6"><div><h1 class="text-2xl font-bold">Booking Masuk</h1><p class="text-sm text-gray-500">Booking pada resource milikmu.</p></div><a href="{{ url('/provider/dashboard') }}" class="text-sm hover:underline">← Dashboard</a></div>
    <div class="flex gap-2 mb-4"><select x-model="status" @change="load()" class="border rounded-lg px-3 py-2 text-sm"><option value="">Semua status</option><option value="pending_payment">Menunggu Bayar</option><option value="confirmed">Terkonfirmasi</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option><option value="expired">Kedaluwarsa</option><option value="refunded">Direfund</option></select></div>
    <div class="bg-white border rounded-xl divide-y"><template x-for="b in bookings" :key="b.id"><a :href="'{{ url('/bookings') }}/'+b.id" class="block p-4 hover:bg-gray-50"><div class="flex justify-between"><div><p class="font-semibold" x-text="b.resource?.name"></p><p class="text-xs text-gray-500" x-text="b.booking_code"></p></div><div class="text-right"><p class="font-medium" x-text="'Rp '+Number(b.total_price).toLocaleString('id-ID')"></p><span class="text-xs" x-text="b.status"></span></div></div></a></template><template x-if="!loading && bookings.length===0"><div class="p-5 text-sm text-gray-400">Tidak ada booking.</div></template></div>
</div>
@push('scripts')
<script>
function providerBookings(){return{bookings:[],status:'',loading:false,async load(){this.loading=true;try{const q=this.status?'?status='+this.status:'';const d=await apiFetch('/provider/dashboard/bookings'+q);this.bookings=d.data||[]}catch(e){if(e.status===401)location.href='{{ url('/login') }}';else if(e.status===403)alert('Khusus provider.');else alert(e.message)}finally{this.loading=false}}}}
</script>
@endpush
@endsection
