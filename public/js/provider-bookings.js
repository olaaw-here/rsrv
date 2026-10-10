/**
 * RSRV — Provider: Booking Masuk
 * Alpine component: providerBookingsPage()
 */
function providerBookingsPage() {
    return {
        bookings:        [],
        loading:         false,
        error:           '',
        notice:          '',
        search:          '',
        activeFilter:    'all',
        selectedBooking: null,
        stats:           [],
        workingId:       null,

        async init() {
            await this.loadBookings();
        },

        /** Authenticated fetch wrapper khusus halaman ini. */
        async api(url, options = {}) {
            const token = localStorage.getItem('token') || '';
            const r = await fetch(url, {
                ...options,
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    ...(token ? { 'Authorization': Bearer  } : {}),
                    ...(options.headers || {}),
                },
            });
            const d = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(d.message || 'Permintaan gagal. Periksa login dan endpoint API.');
            return d;
        },

        async loadBookings() {
            this.loading = true;
            this.error   = '';

            try {
                const p = new URLSearchParams();
                if (this.activeFilter !== 'all') p.set('status', this.activeFilter);
                if (this.search.trim()) p.set('search', this.search.trim());

                const r = await this.api('/api/provider/bookings' + (p.toString() ? '?' + p : ''));
                const d = Array.isArray(r) ? r : (r.data || r.bookings || []);
                this.bookings = Array.isArray(d) ? d : (d.data || []);

                this.stats = [
                    { key: 'all',             label: 'Semua booking',        value: this.bookings.length },
                    { key: 'pending_payment', label: 'Menunggu pembayaran',  value: this.bookings.filter(x => x.status === 'pending_payment').length },
                    { key: 'confirmed',       label: 'Terkonfirmasi',        value: this.bookings.filter(x => x.status === 'confirmed').length },
                    { key: 'completed',       label: 'Selesai',              value: this.bookings.filter(x => x.status === 'completed').length },
                ];
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        get filteredBookings() {
            const q = this.search.trim().toLowerCase();
            return this.bookings.filter(b =>
                (this.activeFilter === 'all' || b.status === this.activeFilter) &&
                (!q || [
                    b.booking_code, b.code, b.id,
                    b.resource?.name, b.resource_name,
                    b.user?.name, b.customer?.name, b.customer_name,
                ].join(' ').toLowerCase().includes(q))
            );
        },

        async showDetails(b) {
            this.selectedBooking = b;
            try {
                const r = await this.api(/api/provider/bookings/);
                this.selectedBooking = r.data || r.booking || r;
            } catch (e) {
                // Tetap tampilkan data lokal jika fetch detail gagal.
            }
        },

        async performAction(b, a) {
            const label = { confirm: 'mengonfirmasi', complete: 'menandai selesai', reject: 'menolak' }[a];
            let reason  = '';

            if (a === 'reject') {
                reason = prompt('Masukkan alasan penolakan booking (wajib):') || '';
                if (!reason.trim()) { this.error = 'Alasan penolakan wajib diisi.'; return; }
                if (!confirm('Tolak booking ini dengan alasan tersebut?')) return;
            } else {
                if (!confirm(Yakin ingin  booking ini?)) return;
            }

            this.workingId = b.id;
            this.error     = '';
            this.notice    = '';

            try {
                const body = a === 'reject' ? { reason: reason.trim() } : {};
                const r    = await this.api(/api/provider/bookings//, {
                    method: 'POST',
                    body:   JSON.stringify(body),
                });
                this.notice          = r.message || 'Perubahan booking berhasil disimpan.';
                this.selectedBooking = null;
                await this.loadBookings();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.workingId = null;
            }
        },

        statusLabel(s) {
            return ({
                pending_payment: 'Menunggu pembayaran',
                confirmed:       'Terkonfirmasi',
                completed:       'Selesai',
                cancelled:       'Dibatalkan',
                expired:         'Kedaluwarsa',
                refunded:        'Refund',
            })[s] || s || '—';
        },

        statusClass(s) {
            return ({
                pending_payment: 'bg-amber-50 text-amber-700',
                confirmed:       'bg-indigo-50 text-indigo-700',
                completed:       'bg-emerald-50 text-emerald-700',
                cancelled:       'bg-rose-50 text-rose-700',
                expired:         'bg-slate-100 text-slate-600',
                refunded:        'bg-violet-50 text-violet-700',
            })[s] || 'bg-slate-100 text-slate-600';
        },

        formatMoney(v) {
            return new Intl.NumberFormat('id-ID', {
                style:                 'currency',
                currency:              'IDR',
                maximumFractionDigits: 0,
            }).format(Number(v || 0));
        },

        formatDate(v) {
            if (!v) return '—';
            const d = new Date(v);
            return Number.isNaN(d.getTime())
                ? v
                : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(d);
        },
    };
}
