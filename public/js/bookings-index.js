/**
 * RSRV — Bookings: Daftar Booking Customer
 * Alpine component: bookingList()
 */
function bookingList() {
    return {
        bookings:     [],
        loading:      false,
        errorMessage: null,
        status:       '',

        tabs: [
            { value: '',                label: 'Semua' },
            { value: 'pending_payment', label: 'Menunggu Pembayaran' },
            { value: 'confirmed',       label: 'Dikonfirmasi' },
            { value: 'completed',       label: 'Selesai' },
            { value: 'cancelled',       label: 'Dibatalkan' },
            { value: 'refunded',        label: 'Refund' },
        ],

        meta: { current_page: 1, last_page: 1 },

        formatRupiah(v) {
            return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
        },

        statusLabel(s) {
            return {
                pending_payment: 'Menunggu Pembayaran',
                confirmed:       'Dikonfirmasi',
                completed:       'Selesai',
                cancelled:       'Dibatalkan',
                expired:         'Expired',
                refunded:        'Refund',
            }[s] || s;
        },

        statusClass(s) {
            return {
                pending_payment: 'bg-amber-100 text-amber-700',
                confirmed:       'bg-blue-100 text-blue-700',
                completed:       'bg-emerald-100 text-emerald-700',
                cancelled:       'bg-red-100 text-red-700',
                expired:         'bg-slate-100 text-slate-600',
                refunded:        'bg-purple-100 text-purple-700',
            }[s] || 'bg-slate-100 text-slate-600';
        },

        bookingDate(b) {
            const s = (b.booking_slots || [])[0]?.time_slot || {};
            return s.date
                ? ${s.date} ·  - 
                : 'Jadwal belum tersedia';
        },

        async load(page = 1) {
            this.loading      = true;
            this.errorMessage = null;

            try {
                const p = new URLSearchParams({ page, per_page: 10 });
                if (this.status) p.set('status', this.status);

                const d = await apiFetch('/bookings?' + p.toString());
                this.bookings = d.data || [];
                this.meta     = d.meta || this.meta;
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                this.errorMessage = e.message;
            } finally {
                this.loading = false;
            }
        },

        async pay(b) {
            try {
                const r = await apiFetch('/bookings/' + b.id + '/pay', { method: 'POST' });

                if (r.snap_token && window.snap) {
                    window.snap.pay(r.snap_token, {
                        onSuccess: () => this.load(),
                        onPending: () => this.load(),
                        onError:   () => this.load(),
                    });
                } else if (r.payment_url) {
                    window.location.href = r.payment_url;
                }
            } catch (e) {
                alert(e.message);
            }
        },

        async cancel(b) {
            // API mewajibkan alasan pembatalan.
            const reason = prompt('Alasan pembatalan booking ini:');
            if (reason === null) return;
            if (!reason.trim()) { alert('Alasan pembatalan wajib diisi.'); return; }

            try {
                await apiFetch('/bookings/' + b.id + '/cancel', {
                    method: 'POST',
                    body:   { reason: reason.trim() },
                });
                await this.load();
            } catch (e) {
                alert(e.data?.errors
                    ? Object.values(e.data.errors).flat().join('\n')
                    : e.message);
            }
        },
    };
}
