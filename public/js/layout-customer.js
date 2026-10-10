/**
 * RSRV — Customer Layout JS
 * Alpine component authStore untuk layout customer.
 * Tidak memvalidasi role — bisa diakses siapa saja.
 */
function authStore() {
    return {
        token: localStorage.getItem('token'),
        user:  JSON.parse(localStorage.getItem('user') || 'null'),

        init() {
            // Token & user sudah di-load dari localStorage di atas.
        },

        setAuth(token, user) {
            this.token = token;
            this.user  = user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
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
