<?php

use Illuminate\Support\Facades\Route;

/*
| Web routes serve the Blade shell. Data is fetched from routes/api.php.
*/

Route::redirect('/', '/resources');

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

Route::view('/resources', 'resources.index')->name('resources.index');
Route::get('/resources/{resource}', function (int $resource) {
    return view('resources.show', ['resourceId' => $resource]);
})->name('resources.show');

Route::view('/bookings', 'bookings.index')->name('bookings.index');
Route::get('/bookings/{booking}', function (int $booking) {
    return view('bookings.show', ['bookingId' => $booking]);
})->name('bookings.show');

Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
Route::view('/admin/providers', 'admin.providers')->name('admin.providers');
Route::view('/admin/refunds', 'admin.refunds')->name('admin.refunds');

Route::view('/provider/dashboard', 'provider.dashboard')->name('provider.dashboard');
Route::view('/provider/bookings', 'provider.bookings')->name('provider.bookings');
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
