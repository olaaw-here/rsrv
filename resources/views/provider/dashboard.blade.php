@extends('layouts.app')

@section('title', 'Dashboard Provider')

@section('content')
<div x-data="providerDashboard()" x-init="load()" class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">AREA PROVIDER</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight">Kelola bisnis kamu di RSRV.</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Buat resource, atur jam operasional, generate slot, dan pantau booking pelanggan.</p>
        </div>
        <a href="{{ url('/provider/resources/create') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">+ Tambah Resource</a>
    </div>

    <template x-if="providerStatus && providerStatus !== 'active'">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-bold">Profil provider belum aktif.</p>
            <p class="mt-1">Status: <span class="font-semibold" x-text="providerStatus"></span>. CRUD resource akan tersedia setelah admin menyetujui provider.</p>
        </div>
    </template>

    <template x-if="summary">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Booking</p><p class="mt-2 text-3xl font-black" x-text="summary.total_booking"></p></div>
            <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pendapatan</p><p class="mt-2 text-xl font-black" x-text="formatRupiah(summary.total_pendapatan)"></p></div>
            <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Menunggu Bayar</p><p class="mt-2 text-3xl font-black" x-text="summary.pending_payment"></p></div>
            <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rating</p><p class="mt-2 text-3xl font-black" x-text="'⭐ ' + summary.rating_avg"></p></div>
        </div>
    </template>

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ url('/provider/resources') }}" class="rounded-2xl border bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-2xl">🏢</p><h2 class="mt-3 font-bold">Resource Saya</h2><p class="mt-1 text-sm text-slate-500">Tambah, edit, nonaktifkan, dan atur resource.</p>
        </a>
        <a href="{{ url('/provider/resources/create') }}" class="rounded-2xl border bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-2xl">➕</p><h2 class="mt-3 font-bold">Tambah Resource</h2><p class="mt-1 text-sm text-slate-500">Buat tempat, lapangan, atau layanan baru.</p>
        </a>
        <a href="{{ url('/provider/bookings') }}" class="rounded-2xl border bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-2xl">📋</p><h2 class="mt-3 font-bold">Booking Masuk</h2><p class="mt-1 text-sm text-slate-500">Pantau booking pelanggan untuk resource kamu.</p>
        </a>
    </div>

    <section>
        <div class="mb-3 flex items-center justify-between"><h2 class="font-bold text-lg">Booking Terbaru</h2><a href="{{ url('/provider/bookings') }}" class="text-sm font-semibold text-blue-600">Lihat semua →</a></div>
        <template x-if="loading" x-cloak><div class="rounded-2xl border bg-white p-6 text-sm text-slate-400">Memuat...</div></template>
        <template x-if="!loading && bookings.length === 0" x-cloak><div class="rounded-2xl border bg-white p-8 text-center text-sm text-slate-400">Belum ada booking.</div></template>
        <div class="overflow-hidden rounded-2xl border bg-white divide-y">
            <template x-for="b in bookings" :key="b.id">
                <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold" x-text="b.resource.name + ' · ' + b.booking_code"></p><p class="text-xs text-slate-500" x-text="new Date(b.created_at).toLocaleString('id-ID')"></p></div>
                    <div class="text-left sm:text-right"><p class="font-semibold" x-text="formatRupiah(b.total_price)"></p><span class="text-xs text-slate-500" x-text="b.status"></span></div>
                </div>
            </template>
        </div>
    </section>
</div>

@push('scripts')
<script>
function providerDashboard(){return{summary:null,bookings:[],loading:false,providerStatus:null,async load(){this.loading=true;try{this.summary=await apiFetch('/provider/dashboard/summary');const data=await apiFetch('/provider/dashboard/bookings?per_page=10');this.bookings=data.data;this.providerStatus=data.provider_status||this.summary.provider_status||'active';}catch(e){if(e.status===401)window.location.href='{{ url('/login') }}';else if(e.status===403)alert(e.message);}finally{this.loading=false;}}}}
</script>
@endpush
@endsection
