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
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes — Marketplace Jasa / Booking System
|--------------------------------------------------------------------------
| Semua route di file ini di-prefix otomatis dengan /api (bawaan Laravel).
| Controller yang dirujuk di sini BELUM dibuat — ini adalah "kontrak" API
| yang jadi acuan sebelum controller & service layer diisi (step berikutnya).
*/

// =========================================================================
// AUTH (public)
// =========================================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');


// =========================================================================
// PUBLIC — Katalog & Kalender (bisa diakses tanpa login, untuk browsing)
// =========================================================================
Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/resources', [ResourceController::class, 'index']);              // list + filter/search
Route::get('/resources/{resource}', [ResourceController::class, 'show']);    // detail resource

// Kalender ketersediaan slot — inti fitur penjadwalan
Route::get('/resources/{resource}/slots', [TimeSlotController::class, 'index']);
// contoh query: GET /api/resources/12/slots?date=2026-09-15


// =========================================================================
// PAYMENT GATEWAY WEBHOOK (public, tapi wajib verifikasi signature di controller)
// =========================================================================
Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])
    ->withoutMiddleware(['auth:sanctum']) // tegaskan: endpoint ini publik dari sisi HTTP
    ->name('webhooks.midtrans');


// =========================================================================
// CUSTOMER (wajib login)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // Booking
    Route::get('/bookings', [BookingController::class, 'index']);            // riwayat booking saya
    Route::post('/bookings', [BookingController::class, 'store']);           // buat booking (hold slot + inisiasi payment)
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);   // detail booking
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/pay', [BookingController::class, 'initiatePayment']); // re-generate Snap Token jika perlu

    // Review (hanya untuk booking milik sendiri yang sudah completed)
    Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);


    // =====================================================================
    // PROVIDER (wajib login + role=provider, dicek via middleware/policy)
    // =====================================================================
    Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function () {

        // Manajemen resource (tempat/lapangan/jasa) milik provider
        Route::apiResource('resources', ProviderResourceController::class);

        // Jam operasional per resource
        Route::get('/resources/{resource}/hours', [OperationalHourController::class, 'index']);
        Route::put('/resources/{resource}/hours', [OperationalHourController::class, 'update']);

        // Generate & kelola timeslot (auto-generate dari jam operasional, atau blok manual)
        Route::post('/resources/{resource}/slots/generate', [TimeSlotController::class, 'generate']);
        Route::post('/resources/{resource}/slots/{slot}/block', [TimeSlotController::class, 'block']);
        Route::post('/resources/{resource}/slots/{slot}/unblock', [TimeSlotController::class, 'unblock']);

        // Dashboard rekap
        Route::get('/dashboard/summary', [ProviderDashboardController::class, 'summary']);
        Route::get('/dashboard/bookings', [ProviderDashboardController::class, 'bookings']);
        Route::get('/dashboard/bookings/export', [ProviderDashboardController::class, 'exportBookings']);
        Route::get('/resources/{resource}/occupancy', [ProviderDashboardController::class, 'occupancy']);
    });
});