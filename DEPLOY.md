# Panduan Deploy (RSRV)

## Persiapan server
1. PHP ^8.3 (ext: mbstring, xml, curl, pdo_mysql), MySQL/MariaDB, Composer.
2. `composer install --no-dev --optimize-autoloader`
3. `cp .env.example .env` lalu isi:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`
   - `php artisan key:generate` (JANGAN pakai APP_KEY dari lokal)
   - `DB_*` (MySQL), `APP_TIMEZONE=Asia/Jakarta`
   - `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=true`
4. `php artisan migrate --force`
5. Buat admin: `php artisan app:create-admin email@anda.com --name="Nama"` (jangan jalankan db:seed di production)
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Arahkan document root ke `public/`; pastikan `storage/` dan `bootstrap/cache/` writable.

## Wajib: scheduler
Booking yang belum dibayar baru expire jika cron berjalan:
`* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1`

## Midtrans
Set Payment Notification URL di dashboard Midtrans ke `https://domain-anda/api/webhooks/midtrans`.
Webhook ditolak (403) bila `MIDTRANS_SERVER_KEY` kosong.

## Checklist sebelum go-live
- [ ] `php artisan migrate:fresh` di MySQL staging berhasil
- [ ] Alur bayar sandbox end-to-end (bayar, expire, cancel) berhasil
- [ ] HTTPS aktif, APP_DEBUG=false
