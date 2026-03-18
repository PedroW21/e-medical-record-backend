<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Controllers\ExamResultController;
use App\Modules\MedicalRecord\Http\Controllers\LabCatalogController;
use App\Modules\MedicalRecord\Http\Controllers\LabResultController;
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

    // Lab Catalog (read-only)
    Route::get('/lab-catalog', [LabCatalogController::class, 'indexCatalog']);
    Route::get('/lab-catalog/{id}', [LabCatalogController::class, 'showCatalog']);
    Route::get('/lab-panels', [LabCatalogController::class, 'indexPanels']);
    Route::get('/lab-panels/{id}', [LabCatalogController::class, 'showPanel']);

    // Lab Results (nested under medical record)
    Route::get('/medical-records/{medicalRecordId}/lab-results', [LabResultController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/lab-results', [LabResultController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/lab-results/{id}', [LabResultController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/lab-results/{id}', [LabResultController::class, 'destroy']);

    // Structured Exam Results (all 14 types via examType slug)
    $examTypePattern = implode('|', array_map(fn (ExamType $t): string => $t->value, ExamType::cases()));

    Route::get('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'index'])->where('examType', $examTypePattern);
    Route::post('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'store'])->where('examType', $examTypePattern);
    Route::put('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'update'])->where('examType', $examTypePattern);
    Route::delete('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'destroy'])->where('examType', $examTypePattern);
});
