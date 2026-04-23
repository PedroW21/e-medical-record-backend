<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('returns a single attachment with full resource shape', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $attachment->id)
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.attachment_type', $attachment->tipo_anexo->value)
        ->assertJsonPath('data.processing_status', ProcessingStatus::Completed->value)
        ->assertJsonStructure([
            'data' => [
                'id',
                'medical_record_id',
                'patient_id',
                'attachment_type',
                'name',
                'file_type',
                'file_url',
                'file_size',
                'processing_status',
                'extracted_data',
                'processing_error',
                'processed_at',
                'confirmed_at',
                'materialized',
                'lab_analytes_count',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.extracted_data'))->toBeArray();
});

it('returns null materialized and null lab_analytes_count for a completed non-lab anexo without materialised row', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.materialized', null)
        ->assertJsonPath('data.lab_analytes_count', null);
});

it('exposes materialized reference when an exam result is linked to the anexo', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $ecg = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $attachment->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.materialized', ['id' => $ecg->id, 'exam_type' => 'ecg'])
        ->assertJsonPath('data.lab_analytes_count', null);
});

it('exposes lab_analytes_count for lab anexos', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    ValorLaboratorial::factory()->count(5)->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $attachment->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.materialized', null)
        ->assertJsonPath('data.lab_analytes_count', 5);
});

it('returns zero lab_analytes_count for a lab anexo without analytes', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.materialized', null)
        ->assertJsonPath('data.lab_analytes_count', 0);
});

it('returns null materialized and null lab_analytes_count for a documento anexo', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Documento,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/attachments/{$attachment->id}");

    $response->assertOk()
        ->assertJsonPath('data.materialized', null)
        ->assertJsonPath('data.lab_analytes_count', null);
});

it('returns 404 when attachment does not exist', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson('/api/attachments/99999');

    $response->assertNotFound();
});

it('denies viewing an attachment owned by another doctor', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->getJson("/api/attachments/{$attachment->id}");

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->getJson("/api/attachments/{$attachment->id}");

    $response->assertUnauthorized();
});
