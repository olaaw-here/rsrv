/**
 * RSRV — Provider Dashboard
 * Alpine component: providerDashboard()
 */
function providerDashboard() {
    return {
        summary:        null,
        bookings:       [],
        loading:        false,
        providerStatus: null,

        async load() {
            this.loading = true;
            try {
                this.summary = await apiFetch('/provider/dashboard/summary');

                const data         = await apiFetch('/provider/dashboard/bookings?per_page=10');
                this.bookings      = data.data;
                this.providerStatus = data.provider_status || this.summary.provider_status || 'active';
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '/login';
                } else if (e.status === 403) {
                    alert(e.message);
                }
            } finally {
                this.loading = false;
            }
        },

        formatRupiah(v) {
            return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
        },
    };
}
