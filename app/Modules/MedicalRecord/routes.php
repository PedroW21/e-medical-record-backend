<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Controllers\AttachmentController;
use App\Modules\MedicalRecord\Http\Controllers\ExamRequestController;
use App\Modules\MedicalRecord\Http\Controllers\ExamRequestModelController;
use App\Modules\MedicalRecord\Http\Controllers\ExamResultController;
use App\Modules\MedicalRecord\Http\Controllers\LabCatalogController;
use App\Modules\MedicalRecord\Http\Controllers\LabResultController;
use App\Modules\MedicalRecord\Http\Controllers\MedicalReportTemplateController;
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

    // Exam Requests (nested under medical records)
    Route::get('/medical-records/{medicalRecordId}/exam-requests', [ExamRequestController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/exam-requests', [ExamRequestController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/exam-requests/{id}', [ExamRequestController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/exam-requests/{id}', [ExamRequestController::class, 'destroy']);
    Route::post('/medical-records/{medicalRecordId}/exam-requests/{id}/print', [ExamRequestController::class, 'print']);

    // Exam Request Models
    Route::get('/exam-request-models', [ExamRequestModelController::class, 'index']);
    Route::post('/exam-request-models', [ExamRequestModelController::class, 'store']);
    Route::put('/exam-request-models/{id}', [ExamRequestModelController::class, 'update']);
    Route::delete('/exam-request-models/{id}', [ExamRequestModelController::class, 'destroy']);

    // Medical Report Templates
    Route::get('/medical-report-templates', [MedicalReportTemplateController::class, 'index']);
    Route::post('/medical-report-templates', [MedicalReportTemplateController::class, 'store']);
    Route::put('/medical-report-templates/{id}', [MedicalReportTemplateController::class, 'update']);
    Route::delete('/medical-report-templates/{id}', [MedicalReportTemplateController::class, 'destroy']);

    // Structured Exam Results (all 14 types via examType slug)
    $examTypePattern = implode('|', array_map(fn (ExamType $t): string => $t->value, ExamType::cases()));

    Route::get('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'index'])->where('examType', $examTypePattern);
    Route::post('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'store'])->where('examType', $examTypePattern);
    Route::put('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'update'])->where('examType', $examTypePattern);
    Route::delete('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'destroy'])->where('examType', $examTypePattern);

    // Attachments (nested under medical record for listing/creation, flat for single-resource ops)
    Route::get('/medical-records/{medicalRecordId}/attachments', [AttachmentController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{id}', [AttachmentController::class, 'show']);
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::post('/attachments/{id}/retry', [AttachmentController::class, 'retry']);
    Route::post('/attachments/{id}/confirm', [AttachmentController::class, 'confirm']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);
});
