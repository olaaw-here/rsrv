/**
 * RSRV — Admin Dashboard
 * Alpine component: adminDashboard()
 */
function adminDashboard() {
    return {
        loading: true,
        error:   '',

        stats: {
            pending_providers: 0,
            active_providers:  0,
            bookings:          0,
            pending_refunds:   0,
        },

        async loadDashboard() {
            this.loading = true;
            this.error   = '';

            try {
                const token = localStorage.getItem('token') || '';

                if (!token) {
                    throw new Error('Sesi login tidak ditemukan. Silakan login kembali.');
                }

                const response = await fetch('/api/admin/dashboard/summary', {
                    method:  'GET',
                    headers: {
                        Accept:        'application/json',
                        Authorization: Bearer ,
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Tidak dapat mengambil data dashboard.');
                }

                const payload = data.data || data.stats || data;

                this.stats.pending_providers = Number(payload.providers_pending  ?? payload.pending_providers ?? 0);
                this.stats.active_providers  = Number(payload.providers_active   ?? payload.active_providers  ?? 0);
                this.stats.bookings          = Number(payload.total_bookings     ?? payload.bookings          ?? 0);
                this.stats.pending_refunds   = Number(payload.refunds_requested  ?? payload.pending_refunds   ?? 0);

            } catch (error) {
                console.error('Admin dashboard error:', error);
                this.error = error.message || 'Terjadi kesalahan saat mengambil data dashboard.';
            } finally {
                this.loading = false;
            }
        },
    };
}
