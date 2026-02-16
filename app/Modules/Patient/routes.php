<?php

declare(strict_types=1);

use App\Modules\Patient\Http\Controllers\AddressController;
use App\Modules\Patient\Http\Controllers\AllergyController;
use App\Modules\Patient\Http\Controllers\ChronicConditionController;
use App\Modules\Patient\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    Route::get('/allergies', [AllergyController::class, 'index']);
    Route::get('/chronic-conditions', [ChronicConditionController::class, 'index']);

    Route::get('/addresses/zip/{zip}', [AddressController::class, 'lookupByZip']);
});
