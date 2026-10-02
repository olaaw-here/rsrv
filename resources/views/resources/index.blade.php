@extends('layouts.app')

@section('title', 'Cari Tempat & Jasa')

@section('content')
<div x-data="resourceList()" x-init="load()" class="space-y-6">

    <section class="rounded-3xl bg-slate-900 px-6 py-8 text-white sm:px-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-300">RSRV Marketplace</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Cari tempat & jasa yang bisa langsung dipesan.</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
            Temukan lapangan, tempat, dan layanan konsultasi. Pilih jadwal yang tersedia lalu lakukan booking.
        </p>
    </section>

    <section class="card p-4 sm:p-5">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <input type="search"
                       x-model="filters.search"
                       @input.debounce.400ms="load()"
                       placeholder="Cari nama tempat atau jasa..."
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 pl-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <span class="absolute left-3 top-3 text-slate-400">⌕</span>
            </div>

            <select x-model="filters.type" @change="load()"
                    class="rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-blue-500">
                <option value="">Semua tipe</option>
                <option value="tempat">Tempat</option>
                <option value="lapangan">Lapangan</option>
                <option value="konsultasi">Konsultasi</option>
            </select>

            <button @click="resetFilters()"
                    class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50">
                Reset
            </button>
        </div>
    </section>

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Resource tersedia</h2>
            <p class="text-sm text-slate-500" x-text="meta.total + ' resource ditemukan'"></p>
        </div>
        <template x-if="loading">
            <span class="text-sm text-slate-400">Memuat...</span>
        </template>
    </div>

    <template x-if="errorMessage" x-cloak>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div>
    </template>

    <template x-if="!loading && items.length === 0" x-cloak>
        <div class="card p-12 text-center">
            <div class="text-4xl">🔎</div>
            <h2 class="mt-3 font-bold">Resource tidak ditemukan</h2>
            <p class="mt-1 text-sm text-slate-500">Coba kata kunci atau tipe yang berbeda.</p>
        </div>
    </template>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="item in items" :key="item.id">
            <a :href="'{{ url('/resources') }}/' + item.id"
               class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">

                <div class="flex h-36 items-center justify-center bg-slate-100">
                    <template x-if="item.images && item.images.length">
                        <img :src="item.images[0]" :alt="item.name" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!item.images || !item.images.length">
                        <span class="text-4xl" x-text="typeIcon(item.type)"></span>
                    </template>
                </div>

                <div class="p-5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-blue-700"
                              x-text="typeLabel(item.type)"></span>
                        <span class="text-xs font-semibold text-amber-600" x-text="'★ ' + Number(item.provider?.rating_avg || 0).toFixed(1)"></span>
                    </div>

                    <h3 class="mt-3 line-clamp-1 text-lg font-bold group-hover:text-blue-700" x-text="item.name"></h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="(item.provider?.business_name || 'Provider') + ' · ' + (item.provider?.city || '-')"></p>

                    <div class="mt-4 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xs text-slate-400">Mulai dari</p>
                            <p class="font-black text-slate-900" x-text="formatRupiah(item.base_price)"></p>
                            <p class="text-xs text-slate-400">/ slot</p>
                        </div>
                        <span class="text-sm font-bold text-blue-600">Lihat detail →</span>
                    </div>
                </div>
            </a>
        </template>
    </div>

    <template x-if="meta.last_page > 1">
        <div class="flex items-center justify-center gap-3 pt-2">
            <button @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold disabled:opacity-40">
                ← Sebelumnya
            </button>
            <span class="text-sm text-slate-500" x-text="meta.current_page + ' / ' + meta.last_page"></span>
            <button @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold disabled:opacity-40">
                Berikutnya →
            </button>
        </div>
    </template>
</div>

@push('scripts')
<script>
function resourceList() {
    return {
        items: [],
        loading: false,
        errorMessage: null,
        filters: { search: '', type: '' },
        meta: { current_page: 1, last_page: 1, total: 0 },

        typeLabel(type) {
            return { tempat: 'Tempat', lapangan: 'Lapangan', konsultasi: 'Konsultasi' }[type] || type;
        },

        typeIcon(type) {
            return { tempat: '🏢', lapangan: '⚽', konsultasi: '💬' }[type] || '📍';
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        async load(page = 1) {
            this.loading = true;
            this.errorMessage = null;

            try {
                const params = new URLSearchParams({ per_page: 12, page });

                if (this.filters.search) params.set('search', this.filters.search);
                if (this.filters.type) params.set('type', this.filters.type);

                const data = await apiFetch('/resources?' + params.toString());
                this.items = data.data || [];
                this.meta = data.meta || this.meta;
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.loading = false;
            }
        },

        goPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.load(page);
        },

        resetFilters() {
            this.filters = { search: '', type: '' };
            this.load();
        },
    };
}
</script>
@endpush
@endsection
