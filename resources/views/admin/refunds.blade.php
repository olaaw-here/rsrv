@extends('layouts.app')

@section('title', 'Refund')

@section('content')
<div x-data="refundManager()" x-init="load()">
    <div class="flex justify-between items-center mb-6"><div><h1 class="text-2xl font-bold">Refund</h1><p class="text-sm text-gray-500">Kelola refund yang menunggu konfirmasi.</p></div><a href="{{ url('/admin/dashboard') }}" class="text-sm hover:underline">← Dashboard</a></div>
    <div class="flex gap-2 mb-4 text-sm">
        <button @click="status=''; load()" class="px-3 py-1.5 rounded-full border">Semua</button>
        <button @click="status='requested'; load()" class="px-3 py-1.5 rounded-full border">Requested</button>
        <button @click="status='processed'; load()" class="px-3 py-1.5 rounded-full border">Processed</button>
        <button @click="status='rejected'; load()" class="px-3 py-1.5 rounded-full border">Rejected</button>
    </div>
    <div class="bg-white border rounded-xl divide-y">
        <template x-if="loading"><div class="p-5 text-sm text-gray-400">Memuat...</div></template>
        <template x-for="r in refunds" :key="r.id">
            <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div><p class="font-semibold" x-text="r.payment?.booking?.booking_code || 'Booking' "></p><p class="text-sm" x-text="'Rp ' + Number(r.amount).toLocaleString('id-ID')"></p><p class="text-xs text-gray-500" x-text="r.reason || 'Tanpa alasan'"></p><p class="text-xs text-gray-400" x-text="r.status"></p></div>
                <div class="flex gap-2" x-show="r.status === 'requested'">
                    <button @click="process(r.id)" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm">Tandai Selesai</button>
                    <button @click="reject(r.id)" class="border border-red-300 text-red-600 px-3 py-1.5 rounded-lg text-sm">Tolak</button>
                </div>
            </div>
        </template>
        <template x-if="!loading && refunds.length === 0"><div class="p-5 text-sm text-gray-400">Tidak ada refund.</div></template>
    </div>
</div>
@push('scripts')
<script>
function refundManager() {
    return { refunds: [], status: '', loading: false,
        async load() { this.loading=true; try { const q=this.status ? '?status='+this.status : ''; const d=await apiFetch('/admin/refunds'+q); this.refunds=d.data||[]; } catch(e){ if(e.status===401) location.href='{{ url('/login') }}'; else if(e.status===403) alert('Khusus admin.'); else alert(e.message); } finally{this.loading=false;} },
        async process(id){ if(!confirm('Tandai refund ini sebagai selesai?')) return; try{await apiFetch('/admin/refunds/'+id+'/process',{method:'POST'}); await this.load();}catch(e){alert(e.message)} },
        async reject(id){ const reason=prompt('Alasan penolakan (opsional):'); if(reason===null)return; try{await apiFetch('/admin/refunds/'+id+'/reject',{method:'POST',body:{reason}}); await this.load();}catch(e){alert(e.message)} }
    }
}
</script>
@endpush
@endsection
