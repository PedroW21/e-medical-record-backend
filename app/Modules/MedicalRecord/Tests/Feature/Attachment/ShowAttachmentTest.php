<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;

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
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.extracted_data'))->toBeArray();
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
