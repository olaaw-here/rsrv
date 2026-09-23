@extends('layouts.app')

@section('title', $resourceId ? 'Edit Resource' : 'Tambah Resource')

@section('content')
<div class="max-w-lg mx-auto bg-white border rounded-xl p-6"
     x-data="resourceForm({{ $resourceId ?? 'null' }})" x-init="load()">

    <h1 class="text-xl font-bold mb-4" x-text="resourceId ? 'Edit Resource' : 'Tambah Resource Baru'"></h1>

    <template x-if="errorMessage" x-cloak>
        <div class="bg-red-50 text-red-700 text-sm p-3 rounded-lg mb-3 whitespace-pre-line" x-text="errorMessage"></div>
    </template>

    <form @submit.prevent="submit()" class="space-y-3">
        <div>
            <label class="text-sm font-medium">Nama</label>
            <input type="text" x-model="form.name" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>

        <div>
            <label class="text-sm font-medium">Kategori</label>
            <select x-model="form.category_id" required class="w-full border rounded-lg px-3 py-2 mt-1">
                <option value="">-- pilih kategori --</option>
                <template x-for="c in categories" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="text-sm font-medium">Tipe</label>
            <select x-model="form.type" required class="w-full border rounded-lg px-3 py-2 mt-1">
                <option value="tempat">Tempat</option>
                <option value="lapangan">Lapangan</option>
                <option value="konsultasi">Konsultasi</option>
            </select>
        </div>

        <div>
            <label class="text-sm font-medium">Deskripsi</label>
            <textarea x-model="form.description" rows="3" class="w-full border rounded-lg px-3 py-2 mt-1"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Kapasitas</label>
                <input type="number" min="1" x-model="form.capacity" class="w-full border rounded-lg px-3 py-2 mt-1">
            </div>
            <div>
                <label class="text-sm font-medium">Durasi Slot (menit)</label>
                <input type="number" min="15" x-model="form.slot_duration_minutes" required class="w-full border rounded-lg px-3 py-2 mt-1">
            </div>
        </div>

        <div>
            <label class="text-sm font-medium">Harga per Slot (Rp)</label>
            <input type="number" min="0" x-model="form.base_price" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>

        <template x-if="resourceId">
            <div>
                <label class="text-sm font-medium">Status</label>
                <select x-model="form.status" class="w-full border rounded-lg px-3 py-2 mt-1">
                    <option value="draft">Draft</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
        </template>

        <button type="submit" :disabled="saving"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
        </button>
    </form>
</div>

@push('scripts')
<script>
    function resourceForm(resourceId) {
        return {
            resourceId,
            categories: [],
            saving: false,
            errorMessage: null,
            form: {
                name: '', category_id: '', type: 'tempat', description: '',
                capacity: '', slot_duration_minutes: 60, base_price: '', status: 'draft',
            },

            async load() {
                this.categories = await apiFetch('/categories');

                if (this.resourceId) {
                    try {
                        const r = await apiFetch('/provider/resources/' + this.resourceId);
                        this.form = {
                            name: r.name, category_id: r.category.id, type: r.type,
                            description: r.description, capacity: r.capacity,
                            slot_duration_minutes: r.slot_duration_minutes,
                            base_price: r.base_price, status: r.status,
                        };
                    } catch (e) {
                        alert(e.message);
                    }
                }
            },

            async submit() {
                this.saving = true;
                this.errorMessage = null;

                try {
                    if (this.resourceId) {
                        await apiFetch('/provider/resources/' + this.resourceId, { method: 'PUT', body: this.form });
                    } else {
                        await apiFetch('/provider/resources', { method: 'POST', body: this.form });
                    }
                    window.location.href = '{{ url('/provider/resources') }}';
                } catch (e) {
                    this.errorMessage = e.data && e.data.errors
                        ? Object.values(e.data.errors).flat().join('\n')
                        : e.message;
                } finally {
                    this.saving = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
