<?php

use App\Http\Controllers\CheckInController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant')->group(function () {
    Route::post('/rooms/{room}/check-ins', [CheckInController::class, 'store'])->name('rooms.check-ins.store');
    Route::post('/check-ins/{checkIn}/check-out', [CheckInController::class, 'checkOut'])->name('check-ins.check-out');
    Route::get('/rooms/{room}/occupants', [CheckInController::class, 'occupants'])->name('rooms.occupants');
});
