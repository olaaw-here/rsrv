@extends('layouts.app')

@section('title', 'Jam Operasional')

@section('content')
<div class="max-w-2xl mx-auto" x-data="hoursForm({{ $resourceId }})" x-init="load()">

    <h1 class="text-xl font-bold mb-4">Jam Operasional & Generate Slot</h1>

    <div class="bg-white border rounded-xl p-6 mb-6">
        <h2 class="font-semibold mb-3">Jam Operasional per Hari</h2>

        <div class="space-y-2">
            <template x-for="(h, i) in hours" :key="h.day_of_week">
                <div class="flex items-center gap-3">
                    <span class="w-24 text-sm" x-text="dayName(h.day_of_week)"></span>
                    <label class="flex items-center gap-1 text-xs">
                        <input type="checkbox" x-model="h.is_closed"> Tutup
                    </label>
                    <input type="time" x-model="h.open_time" :disabled="h.is_closed" class="border rounded-lg px-2 py-1 text-sm">
                    <span class="text-xs">s/d</span>
                    <input type="time" x-model="h.close_time" :disabled="h.is_closed" class="border rounded-lg px-2 py-1 text-sm">
                </div>
            </template>
        </div>

        <button @click="saveHours()" :disabled="savingHours"
                class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
            <span x-text="savingHours ? 'Menyimpan...' : 'Simpan Jam Operasional'"></span>
        </button>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <h2 class="font-semibold mb-3">Generate Slot Waktu</h2>
        <p class="text-xs text-gray-500 mb-3">
            Buat baris timeslot secara otomatis dari jam operasional di atas, untuk rentang tanggal tertentu.
            Aman dijalankan berulang kali (tidak akan membuat slot duplikat).
        </p>

        <div class="flex gap-3 items-end">
            <div>
                <label class="text-xs">Dari Tanggal</label>
                <input type="date" x-model="generateFrom" class="border rounded-lg px-2 py-1.5 text-sm block">
            </div>
            <div>
                <label class="text-xs">Sampai Tanggal</label>
                <input type="date" x-model="generateTo" class="border rounded-lg px-2 py-1.5 text-sm block">
            </div>
            <button @click="generateSlots()" :disabled="generating"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
                <span x-text="generating ? 'Memproses...' : 'Generate Slot'"></span>
            </button>
        </div>

        <template x-if="generateMessage" x-cloak>
            <p class="text-sm text-green-700 mt-3" x-text="generateMessage"></p>
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

            dayNames: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            dayName(i) { return this.dayNames[i]; },

            async load() {
                try {
                    const existing = await apiFetch('/provider/resources/' + this.resourceId + '/hours');
                    const byDay = Object.fromEntries(existing.map(h => [h.day_of_week, h]));

                    this.hours = [0, 1, 2, 3, 4, 5, 6].map(day => byDay[day] || {
                        day_of_week: day, open_time: '08:00', close_time: '20:00', is_closed: false,
                    });
                } catch (e) {
                    alert(e.message);
                }

                const today = new Date();
                const nextWeek = new Date();
                nextWeek.setDate(today.getDate() + 7);
                this.generateFrom = today.toISOString().slice(0, 10);
                this.generateTo = nextWeek.toISOString().slice(0, 10);
            },

            async saveHours() {
                this.savingHours = true;
                try {
                    await apiFetch('/provider/resources/' + this.resourceId + '/hours', {
                        method: 'PUT',
                        body: { hours: this.hours },
                    });
                    alert('Jam operasional tersimpan.');
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.savingHours = false;
                }
            },

            async generateSlots() {
                this.generating = true;
                this.generateMessage = null;
                try {
                    const res = await apiFetch('/provider/resources/' + this.resourceId + '/slots/generate', {
                        method: 'POST',
                        body: { from: this.generateFrom, to: this.generateTo },
                    });
                    this.generateMessage = res.message;
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.generating = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
