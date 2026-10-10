/**
 * RSRV — Provider: Profil Bisnis
 * Alpine component: providerProfile()
 */
function providerProfile() {
    return {
        profile: null,
        loading: true,
        saving:  false,
        error:   '',
        notice:  '',

        form: {
            business_name: '',
            description:   '',
            address:       '',
            city:          '',
            latitude:      '',
            longitude:     '',
        },

        async load() {
            this.loading = true;
            this.error   = '';

            try {
                const data = await apiFetch('/provider/profile');
                this.profile = data.data || data;

                this.form = {
                    business_name: this.profile.business_name || '',
                    description:   this.profile.description   || '',
                    address:       this.profile.address        || '',
                    city:          this.profile.city           || '',
                    latitude:      this.profile.latitude       ?? '',
                    longitude:     this.profile.longitude      ?? '',
                };
            } catch (e) {
                if (e.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            this.error  = '';
            this.notice = '';

            try {
                const data   = await apiFetch('/provider/profile', { method: 'PUT', body: this.form });
                this.profile = data.data || data;
                this.notice  = 'Profil bisnis berhasil diperbarui.';
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
                active:    'Aktif',
                pending:   'Menunggu Approval',
                suspended: 'Ditangguhkan',
            }[status] || status || '—';
        },
    };
}
