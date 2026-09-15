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


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/resources', [ResourceController::class, 'index']);             
Route::get('/resources/{resource}', [ResourceController::class, 'show']);    
Route::get('/resources/{resource}/slots', [TimeSlotController::class, 'index']);
Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])
    ->withoutMiddleware(['auth:sanctum']) // tegaskan: endpoint ini publik dari sisi HTTP
    ->name('webhooks.midtrans');

Route::middleware('auth:sanctum')->group(function () {

    // Booking
    Route::get('/bookings', [BookingController::class, 'index']);            
    Route::post('/bookings', [BookingController::class, 'store']);           
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);   
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/pay', [BookingController::class, 'initiatePayment']); 

    // Review (hanya untuk booking milik sendiri yang sudah completed)
    Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

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