@extends('layouts.app')

@section('title', 'Verifikasi Provider')

@section('content')
<div x-data="providerManager()" x-init="load()" class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Verifikasi Provider</h1>
            <p class="text-sm text-gray-500">Tinjau pendaftaran provider dan kelola status akun penyedia layanan.</p>
        </div>
        <a href="{{ url('/admin/dashboard') }}" class="text-sm text-blue-600 hover:underline">← Dashboard Admin</a>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="item in filters" :key="item.value">
            <button @click="status=item.value; page=1; load()"
                class="rounded-full border px-4 py-2 text-sm"
                :class="status === item.value ? 'border-blue-600 bg-blue-600 text-white' : 'bg-white hover:bg-gray-50'"
                x-text="item.label"></button>
        </template>
    </div>

    <template x-if="error"><div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div></template>
    <div class="overflow-hidden rounded-xl border bg-white">
        <template x-if="loading"><div class="p-6 text-sm text-gray-500">Memuat data provider...</div></template>
        <template x-if="!loading && providers.length === 0"><div class="p-6 text-sm text-gray-500">Belum ada provider pada filter ini.</div></template>
        <div class="divide-y">
            <template x-for="provider in providers" :key="provider.id">
                <article class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold" x-text="provider.business_name || 'Nama usaha belum diisi'"></h2>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(provider.status)" x-text="statusLabel(provider.status)"></span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600" x-text="provider.user?.name || 'Pengguna tidak tersedia'"></p>
                            <p class="text-sm text-gray-500" x-text="provider.user?.email || '-' "></p>
                            <p class="text-sm text-gray-500" x-text="provider.user?.phone || 'Nomor telepon belum diisi'"></p>
                            <p class="mt-2 text-sm" x-text="[provider.address, provider.city].filter(Boolean).join(', ') || 'Alamat belum diisi'"></p>
                            <p class="mt-2 text-xs text-gray-500" x-text="provider.resources_count + ' resource · ' + provider.total_reviews + ' review · rating ' + Number(provider.rating_avg).toFixed(2)"></p>
                            <p class="mt-2 text-sm text-gray-600 whitespace-pre-line" x-text="provider.description || 'Belum ada deskripsi usaha.'"></p>
                        </div>
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <button x-show="provider.status !== 'active'" @click="changeStatus(provider, 'active')" :disabled="updating === provider.id" class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50">Aktifkan</button>
                            <button x-show="provider.status !== 'suspended'" @click="changeStatus(provider, 'suspended')" :disabled="updating === provider.id" class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-50">Suspend</button>
                            <button x-show="provider.status !== 'pending'" @click="changeStatus(provider, 'pending')" :disabled="updating === provider.id" class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50">Kembalikan ke Pending</button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </div>

    <div class="flex items-center justify-between text-sm text-gray-500">
        <span x-text="meta.total + ' provider' "></span>
        <div class="flex gap-2">
            <button @click="page=Math.max(1, page-1); load()" :disabled="page <= 1 || loading" class="rounded-lg border px-3 py-1.5 disabled:opacity-40">Sebelumnya</button>
            <span class="px-2 py-1.5" x-text="page + ' / ' + (meta.last_page || 1)"></span>
            <button @click="page=Math.min(meta.last_page || 1, page+1); load()" :disabled="page >= (meta.last_page || 1) || loading" class="rounded-lg border px-3 py-1.5 disabled:opacity-40">Berikutnya</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function providerManager() {
    return {
        providers: [], loading: false, updating: null, error: '', status: 'pending', page: 1,
        meta: { total: 0, last_page: 1 },
        filters: [
            { label: 'Menunggu Verifikasi', value: 'pending' },
            { label: 'Aktif', value: 'active' },
            { label: 'Suspended', value: 'suspended' },
            { label: 'Semua', value: '' },
        ],
        async load() {
            this.loading = true; this.error = '';
            try {
                const params = new URLSearchParams({ page: String(this.page), per_page: '20' });
                if (this.status) params.set('status', this.status);
                const result = await apiFetch('/admin/providers?' + params.toString());
                this.providers = result.data || [];
                this.meta = result.meta || { total: this.providers.length, last_page: 1 };
            } catch (e) {
                this.error = e.message || 'Gagal memuat provider.';
                if (e.status === 401) window.location.href = '{{ url('/login') }}';
            } finally { this.loading = false; }
        },
        async changeStatus(provider, nextStatus) {
            const labels = { active: 'mengaktifkan', suspended: 'menangguhkan', pending: 'mengembalikan ke status pending' };
            if (!confirm('Yakin ' + labels[nextStatus] + ' provider "' + (provider.business_name || provider.user?.name || provider.id) + '"?')) return;
            this.updating = provider.id; this.error = '';
            try {
                await apiFetch('/admin/providers/' + provider.id + '/status', { method: 'PATCH', body: { status: nextStatus } });
                await this.load();
            } catch (e) { this.error = e.message || 'Status provider gagal diperbarui.'; }
            finally { this.updating = null; }
        },
        statusLabel(value) { return ({ pending: 'Menunggu Verifikasi', active: 'Aktif', suspended: 'Suspended' })[value] || value; },
        statusClass(value) { return ({ pending: 'bg-yellow-100 text-yellow-800', active: 'bg-green-100 text-green-800', suspended: 'bg-red-100 text-red-800' })[value] || 'bg-gray-100 text-gray-700'; },
    };
}
</script>
@endpush
@endsection
