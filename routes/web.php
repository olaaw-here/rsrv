<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — hanya menyajikan shell Blade + Alpine.js.
| Semua data diambil client-side lewat fetch() ke routes/api.php,
| jadi controller di sini sengaja tidak dibutuhkan (cukup closure/view).
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/resources');

// Auth
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

// Publik — katalog & kalender
Route::view('/resources', 'resources.index')->name('resources.index');
Route::get('/resources/{resource}', function (int $resource) {
    return view('resources.show', ['resourceId' => $resource]);
})->name('resources.show');

// Customer — booking (halaman butuh login, tapi pengecekan sesungguhnya
// tetap di sisi API; kalau token tidak ada, JS akan redirect ke /login)
Route::view('/bookings', 'bookings.index')->name('bookings.index');
Route::get('/bookings/{booking}', function (int $booking) {
    return view('bookings.show', ['bookingId' => $booking]);
})->name('bookings.show');

// Provider
Route::view('/provider/dashboard', 'provider.dashboard')->name('provider.dashboard');
Route::view('/provider/resources', 'provider.resources.index')->name('provider.resources.index');
Route::get('/provider/resources/create', function () {
    return view('provider.resources.form', ['resourceId' => null]);
})->name('provider.resources.create');
Route::get('/provider/resources/{resource}/edit', function (int $resource) {
    return view('provider.resources.form', ['resourceId' => $resource]);
})->name('provider.resources.edit');
Route::get('/provider/resources/{resource}/hours', function (int $resource) {
    return view('provider.resources.hours', ['resourceId' => $resource]);
})->name('provider.resources.hours');
