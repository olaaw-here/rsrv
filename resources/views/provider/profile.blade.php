@extends('layouts.provider')

@section('title', 'Profil Bisnis')

@section('content')
<div x-data="providerProfile()" x-init="load()" class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-pvdr-600">Pengaturan Provider</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight">Profil Bisnis</h1>
            <p class="mt-2 text-sm text-slate-500">
                Kelola informasi bisnis yang akan dilihat customer.
            </p>
        </div>

        <a href="{{ url('/provider/dashboard') }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
            ← Dashboard Provider
        </a>
    </div>

    <template x-if="error" x-cloak>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>
    </template>

    <template x-if="notice" x-cloak>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700" x-text="notice"></div>
    </template>

    <template x-if="loading">
        <div class="card p-10 text-center text-sm text-slate-400">Memuat profil...</div>
    </template>

    <template x-if="!loading">
        <div class="space-y-6">

            <section class="card overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-green-500 px-6 py-7 text-white">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-2xl font-black"
                                 x-text="form.business_name ? form.business_name.charAt(0).toUpperCase() : 'P'"></div>
                            <div>
                                <p class="text-sm text-emerald-50">Profil bisnis</p>
                                <h2 class="mt-1 text-2xl font-black" x-text="form.business_name || 'Nama bisnis'"></h2>
                            </div>
                        </div>

                        <span class="w-fit rounded-full bg-white/20 px-3 py-1.5 text-xs font-bold"
                              x-text="statusLabel(profile?.status)"></span>
                    </div>
                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">Rating</p>
                        <p class="mt-1 text-xl font-black" x-text="Number(profile?.rating_avg || 0).toFixed(1) + ' / 5'"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">Total Review</p>
                        <p class="mt-1 text-xl font-black" x-text="profile?.total_reviews || 0"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">Total Layanan</p>
                        <p class="mt-1 text-xl font-black" x-text="profile?.resources_count || 0"></p>
                    </div>
                </div>
            </section>

            <form @submit.prevent="save()" class="card p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Informasi Bisnis</h2>
                    <p class="mt-1 text-sm text-slate-500">Informasi ini digunakan pada katalog dan detail layanan.</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-slate-700">Nama Bisnis</label>
                    <input x-model="form.business_name" required maxlength="150"
                           class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100">
                </div>

                <div>
                    <label class="text-sm font-semibold text-slate-700">Deskripsi</label>
                    <textarea x-model="form.description" rows="4" maxlength="5000"
                              placeholder="Ceritakan bisnis atau layanan kamu..."
                              class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100"></textarea>
                </div>

                <div>
                    <label class="text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea x-model="form.address" rows="2" maxlength="500"
                              class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-pvdr-500 focus:ring-2 focus:ring-pvdr-100"></textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Kota</label>
                        <input x-model="form.city" maxlength="100"
                               class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Latitude</label>
                        <input type="number" step="any" x-model="form.latitude"
                               class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Longitude</label>
                        <input type="number" step="any" x-model="form.longitude"
                               class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-5">
                    <button type="submit" :disabled="saving"
                            class="rounded-xl bg-pvdr-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-pvdr-700 disabled:opacity-50"
                            x-text="saving ? 'Menyimpan...' : 'Simpan Profil'">
                    </button>
                </div>
            </form>

            <section class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="font-semibold text-blue-900">Informasi akun</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="text-xs text-blue-600">Nama akun</p>
                        <p class="mt-1 font-semibold text-blue-900" x-text="profile?.user?.name || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Email</p>
                        <p class="mt-1 font-semibold text-blue-900" x-text="profile?.user?.email || '-'"></p>
                    </div>
                </div>
            </section>
        </div>
    </template>
</div>

@push('scripts')
<script>
function providerProfile() {
    return {
        profile: null,
        loading: true,
        saving: false,
        error: '',
        notice: '',
        form: {
            business_name: '',
            description: '',
            address: '',
            city: '',
            latitude: '',
            longitude: '',
        },

        async load() {
            this.loading = true;
            this.error = '';

            try {
                const data = await apiFetch('/provider/profile');
                this.profile = data.data || data;

                this.form = {
                    business_name: this.profile.business_name || '',
                    description: this.profile.description || '',
                    address: this.profile.address || '',
                    city: this.profile.city || '',
                    latitude: this.profile.latitude ?? '',
                    longitude: this.profile.longitude ?? '',
                };
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '{{ url('/login') }}';
                    return;
                }

                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            this.error = '';
            this.notice = '';

            try {
                const data = await apiFetch('/provider/profile', {
                    method: 'PUT',
                    body: this.form,
                });

                this.profile = data.data || data;
                this.notice = 'Profil bisnis berhasil diperbarui.';
            } catch (e) {
                this.error = e.data?.errors
                    ? Object.values(e.data.errors).flat().join('\n')
                    : e.message;
            } finally {
                this.saving = false;
            }
        },

        statusLabel(status) {
            return {
                active: 'Aktif',
                pending: 'Menunggu Approval',
                suspended: 'Ditangguhkan',
            }[status] || status || '—';
        },
    };
}
</script>
@endpush
@endsection
