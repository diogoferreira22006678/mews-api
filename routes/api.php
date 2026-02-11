<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MewsAvailabilityController;
use App\Http\Controllers\MewsReservationsController;
use App\Http\Controllers\MewsCustomersController;
use App\Http\Controllers\ChatbotController;
Route::options('/{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('throttle:100,60')->group(function () {
        Route::get('/mews/availability', [MewsAvailabilityController::class, 'index']);
        Route::get('/mews/reservations', [MewsReservationsController::class, 'index']);
        Route::get('/mews/customers/search', [MewsCustomersController::class, 'search']);
    });

});
Route::post('/chatbot/message', [ChatbotController::class, 'message']);