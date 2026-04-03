<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;

it('creates an exam request with items', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
            ['id' => 'glicemia', 'name' => 'Glicemia em jejum', 'tuss_code' => '40302213', 'selected' => true],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.items.0.id', 'hemograma')
        ->assertJsonPath('data.items.0.name', 'Hemograma completo');

    $this->assertDatabaseHas('solicitacoes_exames', [
        'prontuario_id' => $prontuario->id,
    ]);
});

it('creates an exam request with medical report', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
        ],
        'medical_report' => [
            'template_id' => null,
            'body' => 'Solicito os exames para acompanhamento clínico do paciente.',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.medical_report.body', 'Solicito os exames para acompanhamento clínico do paciente.');

    $record = SolicitacaoExame::query()->where('prontuario_id', $prontuario->id)->firstOrFail();

    expect($record->relatorio_medico)->not->toBeNull()
        ->and($record->relatorio_medico['body'])->toBe('Solicito os exames para acompanhamento clínico do paciente.');
});

it('creates an exam request with cid_10 and clinical indication', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
        ],
        'cid_10' => 'E11.9',
        'clinical_indication' => 'Acompanhamento de diabetes mellitus tipo 2.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.cid_10', 'E11.9')
        ->assertJsonPath('data.clinical_indication', 'Acompanhamento de diabetes mellitus tipo 2.');

    $this->assertDatabaseHas('solicitacoes_exames', [
        'prontuario_id' => $prontuario->id,
        'cid_10' => 'E11.9',
        'indicacao_clinica' => 'Acompanhamento de diabetes mellitus tipo 2.',
    ]);
});

it('rejects store on finalized medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
        ],
    ]);

    $response->assertStatus(409);
});

it('rejects store without items', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);
});

it('rejects store by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
        ],
    ]);

    $response->assertForbidden();
});

it('validates item structure', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/exam-requests", [
        'items' => [
            ['tuss_code' => '40302566'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.id', 'items.0.name', 'items.0.selected']);
});
