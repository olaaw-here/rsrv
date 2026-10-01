@extends('layouts.app')

@section('title', 'Resource Saya')

@section('content')
<div x-data="providerResourceList()" x-init="load()">

    <div class="flex justify-between items-center mb-6">
        <div><p class="text-sm font-semibold text-blue-600">PROVIDER</p><h1 class="text-2xl font-black">Resource Saya</h1><p class="text-sm text-slate-500 mt-1">Kelola resource yang akan ditawarkan kepada pelanggan.</p></div>
        <a href="{{ url('/provider/resources/create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            + Tambah Resource
        </a>
    </div>

    <template x-if="loading" x-cloak><p class="text-gray-400 text-sm">Memuat...</p></template>
    <template x-if="!loading && items.length === 0" x-cloak><p class="text-gray-400 text-sm">Belum ada resource. Tambahkan yang pertama!</p></template>

    <div class="space-y-3 mt-5">
        <template x-for="r in items" :key="r.id">
            <div class="bg-white border rounded-2xl p-5 flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center hover:shadow-sm">
                <div>
                    <p class="font-semibold" x-text="r.name"></p>
                    <p class="text-xs text-gray-500" x-text="r.type + ' · ' + r.category.name"></p>
                    <span class="text-xs px-2 py-0.5 rounded-full mt-1 inline-block"
                          :class="r.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                          x-text="r.status"></span>
                </div>
                <div class="flex gap-2 text-sm">
                    <a :href="'{{ url('/provider/resources') }}/' + r.id + '/hours'" class="text-blue-600 hover:underline">Jam Operasional</a>
                    <a :href="'{{ url('/provider/resources') }}/' + r.id + '/edit'" class="text-blue-600 hover:underline">Edit</a>
                    <button @click="remove(r)" class="text-red-600 hover:underline">Hapus</button>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function providerResourceList() {
        return {
            items: [],
            loading: false,

            async load() {
                this.loading = true;
                try {
                    const data = await apiFetch('/provider/resources');
                    this.items = data.data;
                } catch (e) {
                    if (e.status === 401) window.location.href = '{{ url('/login') }}';
                } finally {
                    this.loading = false;
                }
            },

            async remove(r) {
                if (!confirm('Hapus/nonaktifkan resource "' + r.name + '"?')) return;
                try {
                    await apiFetch('/provider/resources/' + r.id, { method: 'DELETE' });
                    this.load();
                } catch (e) {
                    alert(e.message);
                }
            },
        };
    }
</script>
@endpush
@endsection
