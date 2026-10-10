/**
 * RSRV — Admin Layout JS
 * Alpine component authStore untuk layout admin.
 * Redirect ke /resources jika user bukan admin.
 */
function authStore() {
    return {
        token: localStorage.getItem('token'),
        user:  JSON.parse(localStorage.getItem('user') || 'null'),

        async init() {
            if (!this.token) {
                window.location.href = '/login';
                return;
            }

            try {
                const response = await fetch('/api/me', {
                    headers: {
                        'Accept':        'application/json',
                        'Authorization': 'Bearer ' + this.token,
                    },
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || data?.role !== 'admin') {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    window.location.href = '/resources';
                    return;
                }

                this.user = data;
                localStorage.setItem('user', JSON.stringify(data));
            } catch (e) {
                window.location.href = '/login';
            }
        },

        logout() {
            apiFetch('/logout', { method: 'POST' }).finally(() => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            });
        },
    };
}
