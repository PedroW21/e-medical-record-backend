<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\Patient\Models\Paciente;

it('requires authentication', function (): void {
    $patient = Paciente::factory()->create();

    $this->getJson("/api/patients/{$patient->id}/metrics/hemoglobin/history")->assertUnauthorized();
});

it('returns metric metadata with history ordered ascending', function (): void {
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
        'valor_numerico' => 13.8,
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $patient->id,
        'data_coleta' => '2026-01-10',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor_numerico' => 13.5,
    ]);

    $response = $this->actingAs($doctor)
        ->getJson("/api/patients/{$patient->id}/metrics/hemoglobin/history");

    $response->assertOk()
        ->assertJsonPath('data.metric_id', 'hemoglobin')
        ->assertJsonPath('data.metric_name', 'Hemoglobina')
        ->assertJsonPath('data.unit', 'g/dL')
        ->assertJsonPath('data.ref_min', 12)
        ->assertJsonPath('data.ref_max', 17.5)
        ->assertJsonPath('data.color', '#DC2626')
        ->assertJsonCount(2, 'data.history')
        ->assertJsonPath('data.history.0.date', '2026-01-10')
        ->assertJsonPath('data.history.0.value', 13.5)
        ->assertJsonPath('data.history.1.date', '2026-03-15')
        ->assertJsonPath('data.history.1.value', 13.8);
});

it('returns empty history when the patient has no values for the metric', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)
        ->getJson("/api/patients/{$patient->id}/metrics/hemoglobin/history");

    $response->assertOk()
        ->assertJsonPath('data.metric_id', 'hemoglobin')
        ->assertJsonPath('data.history', []);
});

it('returns 404 for an unknown metric id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($doctor)
        ->getJson("/api/patients/{$patient->id}/metrics/unknown_metric/history")
        ->assertNotFound();
});

it('returns 404 when the patient belongs to another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherDoctor->id]);

    $this->actingAs($doctor)
        ->getJson("/api/patients/{$patient->id}/metrics/hemoglobin/history")
        ->assertNotFound();
});
