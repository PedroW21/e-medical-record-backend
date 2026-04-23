<?php

declare(strict_types=1);

use App\Modules\Metrics\Http\Controllers\PatientMetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/patients/{id}/metrics', [PatientMetricsController::class, 'index']);
    Route::get('/patients/{id}/metrics/{metricId}/history', [PatientMetricsController::class, 'history']);
});
