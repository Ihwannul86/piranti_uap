<?php

use App\Http\Controllers\SensorController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [SensorController::class, 'events']);
Route::get('/dashboard', function () {
    return view('dashboard');
});
