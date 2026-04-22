<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Event;

it('confirms a completed attachment and stores the exam data', function (): void {
    Event::fake([AttachmentConfirmed::class]);

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $examData = [
        'date' => '2026-04-20',
        'pattern' => 'sinusal',
        'heart_rate' => 72,
    ];

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => $examData]
    );

    $response->assertOk()
        ->assertJsonPath('data.processing_status', ProcessingStatus::Confirmed->value)
        ->assertJsonPath('data.extracted_data.pattern', 'sinusal')
        ->assertJsonPath('data.extracted_data.heart_rate', 72);

    expect($response->json('data.confirmed_at'))->not->toBeNull();

    $fresh = $attachment->fresh();
    expect($fresh->status_processamento)->toBe(ProcessingStatus::Confirmed);
    expect($fresh->dados_extraidos)->toBe($examData);
    expect($fresh->confirmado_em)->not->toBeNull();

    Event::assertDispatched(AttachmentConfirmed::class);
});

it('forbids confirm when status is pending', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
        'status_processamento' => ProcessingStatus::Pending,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertStatus(409);
});

it('forbids confirm when status is failed', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->failed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertStatus(409);
});

it('forbids confirm when status is already confirmed', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertStatus(409);
});

it('forbids confirm for documento type', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Documento,
        'status_processamento' => null,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertStatus(409);
});

it('rejects missing exam_data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        []
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['exam_data']);
});

it('rejects non-array exam_data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => 'not-an-array']
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['exam_data']);
});

it('denies confirm to non-owner doctor', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctorB)->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->postJson(
        "/api/attachments/{$attachment->id}/confirm",
        ['exam_data' => ['foo' => 'bar']]
    );

    $response->assertUnauthorized();
});
