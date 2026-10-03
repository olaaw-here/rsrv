<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\Provider\ProviderResourceController;
use App\Http\Controllers\Api\Provider\OperationalHourController;
use App\Http\Controllers\Api\Provider\ProviderDashboardController;
use App\Http\Controllers\Api\Provider\ProviderProfileController;
use App\Http\Controllers\Api\Provider\ProviderBookingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\ProviderApprovalController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\RefundController;

/*
|--------------------------------------------------------------------------
| API Routes — Marketplace Jasa / Booking System
|--------------------------------------------------------------------------
| Semua route di file ini di-prefix otomatis dengan /api (bawaan Laravel).
*/

// =========================================================================
// AUTH (public)
// =========================================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me',        [AuthController::class, 'me'])->middleware('auth:sanctum');


// =========================================================================
// PUBLIC — Katalog & Kalender (bisa diakses tanpa login, untuk browsing)
// =========================================================================
Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/resources',           [ResourceController::class, 'index']);   // list + filter/search
Route::get('/resources/{resource}', [ResourceController::class, 'show']);   // detail resource

// Kalender ketersediaan slot — inti fitur penjadwalan
Route::get('/resources/{resource}/slots', [TimeSlotController::class, 'index']);
// contoh query: GET /api/resources/12/slots?date=2026-09-15

// tes ombak
// =========================================================================
// PAYMENT GATEWAY WEBHOOK (public, tapi wajib verifikasi signature)
// =========================================================================
Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])
    ->withoutMiddleware(['auth:sanctum'])
    ->name('webhooks.midtrans');


// =========================================================================
// ADMIN
// =========================================================================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard/summary',                      [AdminDashboardController::class, 'summary']);
    Route::get('/dashboard/bookings',                     [AdminDashboardController::class, 'recentBookings']);
    Route::get('/providers',                              [ProviderApprovalController::class, 'index']);
    Route::patch('/providers/{providerProfile}/status',   [ProviderApprovalController::class, 'update']);
    Route::get('/refunds',                                [RefundController::class, 'index']);
    Route::post('/payments/{payment}/refund',             [RefundController::class, 'requestRefund']);
    Route::post('/refunds/{refund}/process',              [RefundController::class, 'process']);
    Route::post('/refunds/{refund}/reject',               [RefundController::class, 'reject']);
});


// =========================================================================
// AUTHENTICATED (customer & provider)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // ── CUSTOMER: Booking ─────────────────────────────────────────────────────
    Route::get('/bookings',                          [BookingController::class, 'index']);          // riwayat booking saya
    Route::post('/bookings',                         [BookingController::class, 'store']);          // buat booking baru
    Route::get('/bookings/{booking}',                [BookingController::class, 'show']);           // detail booking
    Route::patch('/bookings/{booking}/notes',        [BookingController::class, 'updateNotes']);    // update catatan
    Route::post('/bookings/{booking}/cancel',        [BookingController::class, 'cancel']);         // batalkan booking
    Route::post('/bookings/{booking}/pay',           [BookingController::class, 'initiatePayment']); // re-generate Snap Token

    // ── CUSTOMER: Review ──────────────────────────────────────────────────────
    Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);

    // ── Notifikasi ────────────────────────────────────────────────────────────
    Route::get('/notifications',                           [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read',      [NotificationController::class, 'markAsRead']);


    // =========================================================================
    // PROVIDER (wajib role=provider)
    // =========================================================================
    Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function () {

        // ── Profil Provider (CRUD) ────────────────────────────────────────────
        // GET  /api/provider/profile           → lihat profil sendiri
        // PUT  /api/provider/profile           → update profil (business_name, desc, address, kota, lat/lng)
        // GET  /api/provider/profile/resources → daftar resource milik provider (+ filter status)
        // GET  /api/provider/profile/bookings  → semua booking yang masuk ke resource provider
        Route::get('/profile',                    [ProviderProfileController::class, 'show']);
        Route::put('/profile',                    [ProviderProfileController::class, 'update']);
        Route::get('/profile/resources',          [ProviderProfileController::class, 'resources']);
        Route::get('/profile/bookings',           [ProviderProfileController::class, 'bookings']);

        // ── Manajemen Resource (CRUD penuh) ──────────────────────────────────
        // GET    /api/provider/resources           → index
        // POST   /api/provider/resources           → store
        // GET    /api/provider/resources/{id}      → show
        // PUT    /api/provider/resources/{id}      → update
        // DELETE /api/provider/resources/{id}      → destroy (soft: nonaktifkan jika ada booking)
        Route::apiResource('resources', ProviderResourceController::class);

        // ── Jam Operasional per Resource ──────────────────────────────────────
        Route::get('/resources/{resource}/hours', [OperationalHourController::class, 'index']);
        Route::put('/resources/{resource}/hours', [OperationalHourController::class, 'update']);

        // ── Time Slot: Generate & Blokir ─────────────────────────────────────
        Route::post('/resources/{resource}/slots/generate',              [TimeSlotController::class, 'generate']);
        Route::post('/resources/{resource}/slots/{slot}/block',          [TimeSlotController::class, 'block']);
        Route::post('/resources/{resource}/slots/{slot}/unblock',        [TimeSlotController::class, 'unblock']);

        // ── Manajemen Booking Masuk ───────────────────────────────────────────
        // GET  /api/provider/bookings                       → list (filter: status, resource_id, from, to)
        // GET  /api/provider/bookings/{booking}             → detail booking
        // POST /api/provider/bookings/{booking}/confirm     → konfirmasi manual (pending_payment → confirmed)
        // POST /api/provider/bookings/{booking}/complete    → tandai selesai  (confirmed → completed)
        // POST /api/provider/bookings/{booking}/reject      → tolak & lepas slot (pending_payment → cancelled)
        Route::get('/bookings',                              [ProviderBookingController::class, 'index']);
        Route::get('/bookings/{booking}',                    [ProviderBookingController::class, 'show']);
        Route::post('/bookings/{booking}/confirm',           [ProviderBookingController::class, 'confirm']);
        Route::post('/bookings/{booking}/complete',          [ProviderBookingController::class, 'complete']);
        Route::post('/bookings/{booking}/reject',            [ProviderBookingController::class, 'reject']);

        // ── Dashboard Rekap ───────────────────────────────────────────────────
        Route::get('/dashboard/summary',                     [ProviderDashboardController::class, 'summary']);
        Route::get('/dashboard/bookings',                    [ProviderDashboardController::class, 'bookings']);
        Route::get('/dashboard/bookings/{booking}',          [ProviderDashboardController::class, 'showBooking']);
        Route::get('/dashboard/bookings/export',             [ProviderDashboardController::class, 'exportBookings']);
        Route::get('/resources/{resource}/occupancy',        [ProviderDashboardController::class, 'occupancy']);
    });
});
