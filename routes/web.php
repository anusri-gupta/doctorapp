<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;


// Route::get('/', function () {
//     return view('frontend.index');
// });
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/register', [LoginController::class, 'register'])->name('register.doctor');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/login', function () {
    return redirect()->route('login.form');
});

Route::middleware('auth:doctor')->group(function () {
    Route::get('/availability', [AvailabilityController::class, 'create'])->name('availability.create');
    Route::post('/availability', [AvailabilityController::class, 'store'])->name('availability.store');
    Route::get('/availability/{doctor}', [AvailabilityController::class, 'show'])->name('availability.show');
    Route::delete('/availability/slot/{slot}', [AvailabilityController::class, 'destroy'])->name('availability.slot.destroy');

});


