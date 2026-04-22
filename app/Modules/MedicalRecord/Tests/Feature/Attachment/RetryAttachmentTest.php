<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Queue;

it('resets a failed attachment to pending and pushes job', function (): void {
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->failed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertOk()
        ->assertJsonPath('data.processing_status', ProcessingStatus::Pending->value);

    Queue::assertPushed(ParseAttachmentJob::class);
});

it('re-parses a completed attachment', function (): void {
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->parseable()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertOk()
        ->assertJsonPath('data.processing_status', ProcessingStatus::Pending->value);

    Queue::assertPushed(ParseAttachmentJob::class);
});

it('forbids retry for documento type', function (): void {
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Documento,
        'status_processamento' => null,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertStatus(409);
    Queue::assertNotPushed(ParseAttachmentJob::class);
});

it('forbids retry for outro type', function (): void {
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Outro,
        'status_processamento' => null,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertStatus(409);
    Queue::assertNotPushed(ParseAttachmentJob::class);
});

it('denies retry to non-owner doctor', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $attachment = Anexo::factory()->parseable()->failed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->parseable()->failed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->postJson("/api/attachments/{$attachment->id}/retry");

    $response->assertUnauthorized();
});
