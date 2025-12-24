<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes pour les notifications (protégées par Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Routes CRUD pour les notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::put('/notifications/{id}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Routes supplémentaires
    Route::get('/notifications/{id}/stats', [NotificationController::class, 'stats']);
    Route::post('/notifications/{id}/retry', [NotificationController::class, 'retry']);
});
