<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HotelController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public — home page with search form
Route::inertia('/', 'Home', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Flight search (public — no login needed to search)
Route::get('/flights/search', [FlightController::class, 'search'])->name('flights.search');

// Hotel search (public)
Route::get('/hotels/search', [HotelController::class, 'search'])->name('hotels.search');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard / my bookings
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    // Flight booking
    Route::post('/bookings/flight', [BookingController::class, 'storeFlight'])->name('bookings.flight.store');

    // Hotel booking
    Route::post('/bookings/hotel', [BookingController::class, 'storeHotel'])->name('bookings.hotel.store');

    // My bookings list
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');

    // Saved flights
    Route::post('/saved-flights', [BookingController::class, 'saveToggle'])->name('saved.toggle');
    Route::get('/saved-flights', [BookingController::class, 'saved'])->name('saved.index');
});

require __DIR__.'/settings.php';
