<?php

use App\Http\Controllers\SensorController;
use Illuminate\Support\Facades\Route;

Route::post('/sensor', [SensorController::class, 'store']);
Route::get('/history', [SensorController::class, 'history']);
