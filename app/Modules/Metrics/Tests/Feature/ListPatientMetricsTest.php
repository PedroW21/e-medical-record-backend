<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\Patient\Models\Paciente;

it('requires authentication', function (): void {
    $patient = Paciente::factory()->create();

    $this->getJson("/api/patients/{$patient->id}/metrics")->assertUnauthorized();
});

it('returns empty list when patient has no metrics', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/metrics");

    $response->assertOk()
        ->assertExactJson(['data' => [], 'total' => 0]);
});

it('returns wide-format series ordered ascending by date', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $prontuario = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-03-15',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => '13.8',
        'valor_numerico' => 13.8,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-01-10',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => '13.5',
        'valor_numerico' => 13.5,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/metrics");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('total', 2)
        ->assertJsonPath('data.0.date', '2026-01-10')
        ->assertJsonPath('data.0.id', 1)
        ->assertJsonPath('data.0.patient_id', $patient->id)
        ->assertJsonPath('data.0.recorded_by', $doctor->id)
        ->assertJsonPath('data.0.values.hemoglobin', 13.5)
        ->assertJsonPath('data.1.date', '2026-03-15')
        ->assertJsonPath('data.1.values.hemoglobin', 13.8);
});

it('merges multiple metrics collected on the same date into one row', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $prontuario = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-03-15',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor_numerico' => 14.2,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-03-15',
        'catalogo_exame_id' => 'glicemia-jejum',
        'valor_numerico' => 88.0,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/metrics");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.values.hemoglobin', 14.2)
        ->assertJsonPath('data.0.values.glucose', 88);
});

it('skips lab values without a numeric reading', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $prontuario = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-03-15',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => 'Aguardando',
        'valor_numerico' => null,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/metrics");

    $response->assertOk()->assertExactJson(['data' => [], 'total' => 0]);
});

it('skips catalog entries that are not mapped in the registry', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $prontuario = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-03-15',
        'catalogo_exame_id' => 'hemo-rdw',
        'valor_numerico' => 13.2,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/metrics");

    $response->assertOk()->assertExactJson(['data' => [], 'total' => 0]);
});

it('returns 404 when the patient does not exist', function (): void {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)
        ->getJson('/api/patients/999999/metrics')
        ->assertNotFound();
});

it('returns 404 when the patient belongs to another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherDoctor->id]);

    $this->actingAs($doctor)
        ->getJson("/api/patients/{$patient->id}/metrics")
        ->assertNotFound();
});
