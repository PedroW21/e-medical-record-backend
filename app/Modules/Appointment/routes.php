<?php

declare(strict_types=1);

use App\Modules\Appointment\Http\Controllers\AppointmentController;
use App\Modules\Appointment\Http\Controllers\PublicScheduleController;
use App\Modules\Appointment\Http\Controllers\ScheduleSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/types', [AppointmentController::class, 'types']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);

    Route::get('/schedule-settings', [ScheduleSettingsController::class, 'index']);
    Route::put('/schedule-settings', [ScheduleSettingsController::class, 'update']);
});

Route::get('/public/schedule/{slug}/availability', [PublicScheduleController::class, 'availability']);
Route::post('/public/schedule/{slug}/book', [PublicScheduleController::class, 'book']);
