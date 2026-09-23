@extends('layouts.app')

@section('title', 'Cari Tempat / Jasa')

@section('content')
<div x-data="resourceList()" x-init="load()">

    <h1 class="text-2xl font-bold mb-4">Cari Tempat, Lapangan, atau Jasa Konsultasi</h1>

    <div class="flex flex-wrap gap-2 mb-6">
        <input type="text" x-model="filters.search" @input.debounce.400ms="load()"
               placeholder="Cari nama..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[180px]">

        <select x-model="filters.type" @change="load()" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">Semua Tipe</option>
            <option value="tempat">Tempat</option>
            <option value="lapangan">Lapangan</option>
            <option value="konsultasi">Konsultasi</option>
        </select>
    </div>

    <template x-if="loading" x-cloak>
        <p class="text-gray-400 text-sm">Memuat...</p>
    </template>

    <template x-if="!loading && items.length === 0" x-cloak>
        <p class="text-gray-400 text-sm">Tidak ada hasil ditemukan.</p>
    </template>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <template x-for="item in items" :key="item.id">
            <a :href="'{{ url('/resources') }}/' + item.id"
               class="block bg-white border rounded-xl p-4 hover:shadow-md transition">
                <span class="text-xs uppercase tracking-wide text-blue-600 font-semibold" x-text="item.type"></span>
                <h2 class="font-semibold text-lg mt-1" x-text="item.name"></h2>
                <p class="text-sm text-gray-500 mt-1" x-text="item.provider.business_name + ' · ' + (item.provider.city || '-')"></p>
                <p class="text-sm font-medium mt-2" x-text="'Rp ' + Number(item.base_price).toLocaleString('id-ID') + ' / slot'"></p>
            </a>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function resourceList() {
        return {
            items: [],
            loading: false,
            filters: { search: '', type: '' },

            async load() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filters.search) params.set('search', this.filters.search);
                    if (this.filters.type) params.set('type', this.filters.type);

                    const data = await apiFetch('/resources?' + params.toString());
                    this.items = data.data;
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
