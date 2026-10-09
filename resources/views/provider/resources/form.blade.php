@extends('layouts.provider')

@section('title', $resourceId ? 'Edit Resource' : 'Tambah Resource')

@section('content')
<div class="mx-auto max-w-2xl" x-data="resourceForm({{ $resourceId ?? 'null' }})" x-init="load()">

    <div class="mb-6">
        <a href="{{ url('/provider/resources') }}" class="text-sm font-semibold text-pvdr-700 hover:underline">← Kembali ke Resource</a>
        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-pvdr-600">Katalog Saya</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight" x-text="resourceId ? 'Edit Resource' : 'Tambah Resource'"></h1>
        <p class="mt-2 text-sm text-slate-500">Lengkapi informasi resource yang akan ditampilkan kepada pelanggan.</p>
    </div>

    <template x-if="errorMessage" x-cloak>
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 whitespace-pre-line" x-text="errorMessage"></div>
    </template>

    <form @submit.prevent="submit()" class="card p-6 space-y-5">

        <div>
            <label class="text-sm font-semibold text-slate-700">Nama Resource</label>
            <input type="text" x-model="form.name" required maxlength="255"
                   placeholder="Contoh: Lapangan Futsal Galaxy"
                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-semibold text-slate-700">Kategori</label>
                <select x-model="form.category_id" required
                        class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
                    <option value="">-- Pilih kategori --</option>
                    <template x-for="c in categories" :key="c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Tipe</label>
                <select x-model="form.type" required
                        class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
                    <option value="tempat">Tempat</option>
                    <option value="lapangan">Lapangan</option>
                    <option value="konsultasi">Konsultasi</option>
                </select>
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-slate-700">Deskripsi</label>
            <textarea x-model="form.description" rows="4" maxlength="1000"
                      placeholder="Jelaskan fasilitas, aturan, atau informasi penting lainnya."
                      class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100"></textarea>
            <p class="mt-1 text-xs text-slate-400">Maksimal 1000 karakter.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="text-sm font-semibold text-slate-700">Kapasitas</label>
                <input type="number" min="1" x-model="form.capacity"
                       placeholder="Opsional"
                       class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Durasi Slot</label>
                <div class="mt-1.5 flex">
                    <input type="number" min="15" x-model="form.slot_duration_minutes" required
                           class="w-full rounded-l-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
                    <span class="flex items-center rounded-r-xl border border-l-0 border-slate-200 bg-slate-50 px-3 text-xs text-slate-500">menit</span>
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Harga / Slot</label>
                <div class="mt-1.5 flex">
                    <span class="flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-50 px-3 text-xs text-slate-500">Rp</span>
                    <input type="number" min="0" x-model="form.base_price" required
                           class="w-full rounded-r-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-pvdr-500">
                </div>
            </div>
        </div>

        <template x-if="resourceId">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="text-sm font-semibold text-slate-700">Status Resource</label>
                <select x-model="form.status"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <option value="draft">Draft</option>
                    <option value="active">Aktif — tampil untuk customer</option>
                    <option value="inactive">Nonaktif — tidak ditampilkan</option>
                </select>
                <p class="mt-2 text-xs text-slate-500">Resource baru untuk provider aktif langsung tersedia di katalog customer. Provider yang masih menunggu approval tetap membuat resource sebagai Draft.</p>
            </div>
        </template>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ url('/provider/resources') }}"
               class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" :disabled="saving"
                    class="rounded-xl bg-pvdr-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-pvdr-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-text="saving ? 'Menyimpan...' : (resourceId ? 'Simpan Perubahan' : 'Buat Resource')"></span>
            </button>
        </div>
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
            name: '',
            category_id: '',
            type: 'tempat',
            description: '',
            capacity: '',
            slot_duration_minutes: 60,
            base_price: '',
            status: 'draft',
        },

        async load() {
            try {
                const categoriesResponse = await apiFetch('/categories');
                this.categories = categoriesResponse.data || categoriesResponse || [];

                if (this.resourceId) {
                    const r = await apiFetch('/provider/resources/' + this.resourceId);

                    this.form = {
                        name: r.name || '',
                        category_id: r.category?.id || '',
                        type: r.type || 'tempat',
                        description: r.description || '',
                        capacity: r.capacity ?? '',
                        slot_duration_minutes: r.slot_duration_minutes || 60,
                        base_price: r.base_price ?? '',
                        status: r.status || 'draft',
                    };
                }
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '{{ url('/login') }}';
                    return;
                }
                this.errorMessage = e.message;
            }
        },

        async submit() {
            this.saving = true;
            this.errorMessage = null;

            try {
                if (this.resourceId) {
                    await apiFetch('/provider/resources/' + this.resourceId, {
                        method: 'PUT',
                        body: this.form,
                    });
                } else {
                    await apiFetch('/provider/resources', {
                        method: 'POST',
                        body: this.form,
                    });
                }

                window.location.href = '{{ url('/provider/resources') }}';
            } catch (e) {
                this.errorMessage = e.data?.errors
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
