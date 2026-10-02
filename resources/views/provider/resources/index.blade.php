@extends('layouts.provider')

@section('title', 'Resource Saya')

@section('content')
<div x-data="providerResourceList()" x-init="load()" class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-pvdr-600">Katalog Saya</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight">Resource</h1>
            <p class="mt-2 text-sm text-slate-500">Kelola tempat, lapangan, dan layanan yang tersedia untuk pelanggan.</p>
        </div>
        <a href="{{ url('/provider/resources/create') }}"
           class="inline-flex items-center justify-center rounded-xl bg-pvdr-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-pvdr-700">
            + Tambah Resource
        </a>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</p>
            <p class="mt-1 text-2xl font-black" x-text="items.length"></p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Aktif</p>
            <p class="mt-1 text-2xl font-black text-pvdr-600" x-text="items.filter(r => r.status === 'active').length"></p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Draft / Nonaktif</p>
            <p class="mt-1 text-2xl font-black text-slate-600" x-text="items.filter(r => r.status !== 'active').length"></p>
        </div>
    </div>

    <div class="card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <input type="search"
                       x-model="search"
                       placeholder="Cari nama resource..."
                       class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100">
            </div>
            <select x-model="statusFilter"
                    class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="draft">Draft</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>
    </div>

    <template x-if="loading" x-cloak>
        <div class="card p-8 text-center text-sm text-slate-400">Memuat resource...</div>
    </template>

    <template x-if="errorMessage" x-cloak>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div>
    </template>

    <template x-if="!loading && filteredItems().length === 0" x-cloak>
        <div class="card p-10 text-center">
            <div class="text-4xl">📦</div>
            <h2 class="mt-3 font-bold text-slate-800">Belum ada resource</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan resource pertama untuk mulai menawarkan layanan.</p>
            <a href="{{ url('/provider/resources/create') }}"
               class="mt-4 inline-flex rounded-xl bg-pvdr-600 px-4 py-2 text-sm font-bold text-white hover:bg-pvdr-700">
                + Tambah Resource
            </a>
        </div>
    </template>

    <div class="grid gap-4 lg:grid-cols-2">
        <template x-for="r in filteredItems()" :key="r.id">
            <article class="card overflow-hidden">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold" x-text="r.name"></h2>
                            <p class="mt-1 text-sm text-slate-500">
                                <span x-text="typeLabel(r.type)"></span>
                                <span> · </span>
                                <span x-text="r.category?.name || 'Tanpa kategori'"></span>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold"
                              :class="statusClass(r.status)"
                              x-text="statusLabel(r.status)"></span>
                    </div>

                    <p class="mt-4 line-clamp-2 text-sm text-slate-500"
                       x-text="r.description || 'Belum ada deskripsi.'"></p>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-400">Harga / slot</p>
                            <p class="mt-1 font-bold" x-text="formatRupiah(r.base_price)"></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-400">Durasi</p>
                            <p class="mt-1 font-bold" x-text="r.slot_duration_minutes + ' menit'"></p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a :href="'{{ url('/provider/resources') }}/' + r.id + '/hours'"
                           class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                            🕐 Jam & Slot
                        </a>
                        <a :href="'{{ url('/provider/resources') }}/' + r.id + '/edit'"
                           class="rounded-lg border border-pvdr-200 px-3 py-2 text-xs font-semibold text-pvdr-700 hover:bg-pvdr-50">
                            ✏️ Edit
                        </a>
                        <button @click="remove(r)"
                                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                            🗑 Hapus
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </div>
</div>

@push('scripts')
<script>
function providerResourceList() {
    return {
        items: [],
        loading: false,
        search: '',
        statusFilter: '',
        errorMessage: null,

        filteredItems() {
            const keyword = this.search.trim().toLowerCase();

            return this.items.filter(r => {
                const matchesSearch = !keyword || (r.name || '').toLowerCase().includes(keyword);
                const matchesStatus = !this.statusFilter || r.status === this.statusFilter;
                return matchesSearch && matchesStatus;
            });
        },

        statusLabel(status) {
            return {
                active: 'Aktif',
                draft: 'Draft',
                inactive: 'Nonaktif',
            }[status] || status;
        },

        statusClass(status) {
            return {
                active: 'bg-pvdr-100 text-pvdr-700',
                draft: 'bg-amber-100 text-amber-700',
                inactive: 'bg-slate-100 text-slate-500',
            }[status] || 'bg-slate-100 text-slate-500';
        },

        typeLabel(type) {
            return {
                tempat: 'Tempat',
                lapangan: 'Lapangan',
                konsultasi: 'Konsultasi',
            }[type] || type;
        },

        async load() {
            this.loading = true;
            this.errorMessage = null;

            try {
                const data = await apiFetch('/provider/resources');
                this.items = data.data || [];
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '{{ url('/login') }}';
                    return;
                }
                this.errorMessage = e.message;
            } finally {
                this.loading = false;
            }
        },

        async remove(resource) {
            if (!confirm('Hapus/nonaktifkan resource "' + resource.name + '"?')) return;

            try {
                await apiFetch('/provider/resources/' + resource.id, { method: 'DELETE' });
                await this.load();
            } catch (e) {
                alert(e.message);
            }
        },
    };
}
</script>
@endpush
@endsection
