/**
 * RSRV — Bookings: Detail Booking Customer
 * Alpine component: bookingDetail(id)
 */
function bookingDetail(id) {
    return {
        bookingId:    id,
        booking:      null,
        loading:      true,
        processing:   false,
        errorMessage: null,
        rating:       0,
        comment:      '',

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

        async load() {
            try {
                this.booking = await apiFetch('/bookings/' + this.bookingId);
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

        async pay() {
            this.processing = true;
            try {
                const r = await apiFetch('/bookings/' + this.bookingId + '/pay', { method: 'POST' });

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
            } finally {
                this.processing = false;
            }
        },

        async cancel() {
            const reason = prompt('Masukkan alasan pembatalan booking (wajib):') || '';
            if (!reason.trim()) { alert('Alasan pembatalan wajib diisi.'); return; }
            if (!confirm('Batalkan booking dengan alasan tersebut?')) return;

            this.processing = true;
            try {
                await apiFetch('/bookings/' + this.bookingId + '/cancel', {
                    method: 'POST',
                    body:   { reason: reason.trim() },
                });
                await this.load();
            } catch (e) {
                alert(e.message);
            } finally {
                this.processing = false;
            }
        },

        async submitReview() {
            if (!this.rating) return;
            this.processing = true;
            try {
                await apiFetch('/bookings/' + this.bookingId + '/review', {
                    method: 'POST',
                    body:   { rating: this.rating, comment: this.comment },
                });
                await this.load();
            } catch (e) {
                alert(e.message);
            } finally {
                this.processing = false;
            }
        },
    };
}
