@extends('layouts.provider')

@section('title', 'Jam Operasional')

@section('content')
<div class="mx-auto max-w-3xl" x-data="hoursForm({{ $resourceId }})" x-init="load()">

    <div class="mb-6">
        <a href="{{ url('/provider/resources') }}" class="text-sm font-semibold text-pvdr-700 hover:underline">← Kembali ke Resource</a>
        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-pvdr-600">Operasional</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight">Jam Operasional & Slot</h1>
        <p class="mt-2 text-sm text-slate-500">Atur jam buka resource, lalu generate slot yang dapat dipesan pelanggan.</p>
    </div>

    <template x-if="errorMessage" x-cloak>
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="errorMessage"></div>
    </template>

    <div class="card p-6">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold">Jam Operasional Mingguan</h2>
                <p class="mt-1 text-xs text-slate-500">Centang Tutup untuk hari ketika resource tidak beroperasi.</p>
            </div>
            <span class="rounded-full bg-pvdr-50 px-3 py-1 text-xs font-semibold text-pvdr-700">7 hari</span>
        </div>

        <div class="space-y-3">
            <template x-for="h in hours" :key="h.day_of_week">
                <div class="rounded-xl border border-slate-100 p-3">
                    <div class="grid gap-3 sm:grid-cols-[100px_90px_1fr_1fr] sm:items-center">
                        <span class="text-sm font-semibold" x-text="dayName(h.day_of_week)"></span>

                        <label class="flex items-center gap-2 text-xs text-slate-500">
                            <input type="checkbox" x-model="h.is_closed" class="rounded border-slate-300">
                            Tutup
                        </label>

                        <label class="text-xs text-slate-500">
                            Buka
                            <input type="time" x-model="h.open_time" :disabled="h.is_closed"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 px-2 py-2 text-sm disabled:bg-slate-100">
                        </label>

                        <label class="text-xs text-slate-500">
                            Tutup
                            <input type="time" x-model="h.close_time" :disabled="h.is_closed"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 px-2 py-2 text-sm disabled:bg-slate-100">
                        </label>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 flex justify-end">
            <button @click="saveHours()" :disabled="savingHours"
                    class="rounded-xl bg-pvdr-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-pvdr-700 disabled:opacity-50">
                <span x-text="savingHours ? 'Menyimpan...' : 'Simpan Jam Operasional'"></span>
            </button>
        </div>
    </div>

    <div class="card mt-5 p-6">
        <div class="mb-5">
            <h2 class="font-bold">Generate Slot</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">
                Sistem akan membuat slot berdasarkan jam operasional dan durasi resource.
                Aman dijalankan ulang karena slot yang sudah ada tidak dibuat dua kali.
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="text-xs font-semibold text-slate-600">
                Dari tanggal
                <input type="date" x-model="generateFrom"
                       class="mt-1.5 block w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-semibold text-slate-600">
                Sampai tanggal
                <input type="date" x-model="generateTo"
                       class="mt-1.5 block w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </label>

            <button @click="generateSlots()" :disabled="generating"
                    class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-50">
                <span x-text="generating ? 'Memproses...' : 'Generate Slot'"></span>
            </button>
        </div>

        <template x-if="generateMessage" x-cloak>
            <div class="mt-4 rounded-xl bg-pvdr-50 p-3 text-sm font-medium text-pvdr-700" x-text="generateMessage"></div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function hoursForm(resourceId) {
    return {
        resourceId,
        hours: [],
        savingHours: false,
        generating: false,
        generateFrom: null,
        generateTo: null,
        generateMessage: null,
        errorMessage: null,

        dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],

        dayName(i) {
            return this.dayNames[i] || '-';
        },

        defaultDates() {
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(today.getDate() + 7);

            const localDate = date => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            this.generateFrom = localDate(today);
            this.generateTo = localDate(nextWeek);
        },

        async load() {
            this.defaultDates();

            try {
                const existing = await apiFetch('/provider/resources/' + this.resourceId + '/hours');
                const byDay = Object.fromEntries(existing.map(h => [h.day_of_week, h]));

                const timeForInput = value => {
                    if (!value) return '';
                    // API/database may return HH:mm:ss; <input type=time> needs HH:mm.
                    return String(value).slice(0, 5);
                };

                this.hours = [0, 1, 2, 3, 4, 5, 6].map(day => {
                    const saved = byDay[day];
                    if (!saved) {
                        return {
                            day_of_week: day,
                            open_time: '08:00',
                            close_time: '20:00',
                            is_closed: false,
                        };
                    }

                    return {
                        ...saved,
                        open_time: timeForInput(saved.open_time),
                        close_time: timeForInput(saved.close_time),
                        is_closed: Boolean(Number(saved.is_closed)),
                    };
                });
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '{{ url('/login') }}';
                    return;
                }
                this.errorMessage = e.message;
            }
        },

        async saveHours() {
            this.savingHours = true;
            this.errorMessage = null;

            try {
                await apiFetch('/provider/resources/' + this.resourceId + '/hours', {
                    method: 'PUT',
                    body: {
                        hours: this.hours.map(hour => ({
                            ...hour,
                            open_time: hour.is_closed ? null : String(hour.open_time || '').slice(0, 5),
                            close_time: hour.is_closed ? null : String(hour.close_time || '').slice(0, 5),
                            is_closed: Boolean(hour.is_closed),
                        })),
                    },
                });
                alert('Jam operasional tersimpan.');
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.savingHours = false;
            }
        },

        async generateSlots() {
            if (!this.generateFrom || !this.generateTo) {
                this.errorMessage = 'Tanggal generate wajib diisi.';
                return;
            }

            if (this.generateFrom > this.generateTo) {
                this.errorMessage = 'Tanggal mulai tidak boleh setelah tanggal selesai.';
                return;
            }

            this.generating = true;
            this.generateMessage = null;
            this.errorMessage = null;

            try {
                const res = await apiFetch('/provider/resources/' + this.resourceId + '/slots/generate', {
                    method: 'POST',
                    body: {
                        from: this.generateFrom,
                        to: this.generateTo,
                    },
                });

                this.generateMessage = res.message || 'Slot berhasil dibuat.';
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.generating = false;
            }
        },
    };
}
</script>
@endpush
@endsection
