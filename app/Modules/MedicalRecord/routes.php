<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Http\Controllers\MedicationController;
use App\Modules\MedicalRecord\Http\Controllers\PrescriptionController;
use App\Modules\MedicalRecord\Http\Controllers\PrescriptionTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    // Medications (read-only catalog)
    Route::get('/medications', [MedicationController::class, 'index']);
    Route::get('/medications/{id}', [MedicationController::class, 'show']);

    // Prescriptions (nested under medical record)
    Route::get('/medical-records/{medicalRecordId}/prescriptions', [PrescriptionController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/prescriptions', [PrescriptionController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/prescriptions/{id}', [PrescriptionController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/prescriptions/{id}', [PrescriptionController::class, 'destroy']);

    // Prescription templates
    Route::get('/prescription-templates', [PrescriptionTemplateController::class, 'index']);
    Route::post('/prescription-templates', [PrescriptionTemplateController::class, 'store']);
    Route::put('/prescription-templates/{id}', [PrescriptionTemplateController::class, 'update']);
    Route::delete('/prescription-templates/{id}', [PrescriptionTemplateController::class, 'destroy']);
});
