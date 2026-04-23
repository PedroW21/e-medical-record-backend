<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('lists attachments of the given prontuario', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    Anexo::factory()->count(3)->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('excludes attachments from other prontuarios', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    Anexo::factory()->count(2)->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);
    Anexo::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuarioA->id}/attachments"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns empty array when no attachments exist', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertOk()
        ->assertExactJson(['data' => []]);
});

it('denies listing when doctor does not own the prontuario', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->getJson("/api/medical-records/{$prontuario->id}/attachments");

    $response->assertUnauthorized();
});

it('surfaces materialized reference and lab_analytes_count across mixed attachment types', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $ecgAnexo = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);
    $ecg = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $ecgAnexo->id,
    ]);

    $labAnexo = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);
    ValorLaboratorial::factory()->count(3)->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $labAnexo->id,
    ]);

    $documentoAnexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Documento,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertOk()->assertJsonCount(3, 'data');

    $byId = collect($response->json('data'))->keyBy('id');

    expect($byId[$ecgAnexo->id]['materialized'])->toBe(['id' => $ecg->id, 'exam_type' => 'ecg'])
        ->and($byId[$ecgAnexo->id]['lab_analytes_count'])->toBeNull()
        ->and($byId[$labAnexo->id]['materialized'])->toBeNull()
        ->and($byId[$labAnexo->id]['lab_analytes_count'])->toBe(3)
        ->and($byId[$documentoAnexo->id]['materialized'])->toBeNull()
        ->and($byId[$documentoAnexo->id]['lab_analytes_count'])->toBeNull();
});
