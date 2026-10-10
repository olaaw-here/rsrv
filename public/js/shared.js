/**
 * RSRV — Shared JavaScript
 * Extracted from layouts/admin.blade.php, layouts/customer.blade.php,
 * and layouts/provider.blade.php.
 *
 * Loaded in every layout via <script src="/js/shared.js">.
 * Alpine components that depend on apiFetch / authStore must be
 * loaded AFTER this file.
 */

// ─────────────────────────────────────────────────────────────
// apiFetch — authenticated JSON fetch helper
// Usage: const data = await apiFetch('/endpoint', { method: 'POST', body: { key: 'value' } })
// ─────────────────────────────────────────────────────────────
window.apiFetch = async function (path, options = {}) {
    const token = localStorage.getItem('token');

    const res = await fetch('/api' + path, {
        ...options,
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
            ...(options.headers || {}),
        },
        body: options.body ? JSON.stringify(options.body) : undefined,
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
        const error = new Error((data && data.message) || 'Terjadi kesalahan.');
        error.status = res.status;
        error.data   = data;
        throw error;
    }

    return data;
};

// ─────────────────────────────────────────────────────────────
// formatRupiah — format angka ke Rupiah
// Usage: formatRupiah(50000) => 'Rp 50.000'
// ─────────────────────────────────────────────────────────────
window.formatRupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
