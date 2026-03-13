<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('lists lab results grouped by date in v2 format', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);

    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data_coleta' => '2026-03-10',
        'painel_id' => 'hemograma-completo',
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => '14.5',
    ]);

    ValorLaboratorial::factory()->loose()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data_coleta' => '2026-03-10',
        'nome_avulso' => 'VDRL',
        'valor' => 'Não reagente',
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/lab-results"
    );

    $response->assertOk()
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonCount(1, 'data.0.panels')
        ->assertJsonCount(1, 'data.0.loose')
        ->assertJsonPath('data.0.panels.0.panel_id', 'hemograma-completo')
        ->assertJsonPath('data.0.panels.0.values.0.analyte_id', 'hemo-hemoglobina')
        ->assertJsonPath('data.0.panels.0.values.0.value', '14.5')
        ->assertJsonPath('data.0.loose.0.name', 'VDRL')
        ->assertJsonPath('data.0.loose.0.value', 'Não reagente');
});

it('returns empty data when medical record has no lab results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/lab-results"
    );

    $response->assertOk()
        ->assertJsonPath('data', []);
});

it('does not list lab results from another medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    ValorLaboratorial::factory()->loose()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
        'data_coleta' => '2026-03-10',
        'nome_avulso' => 'Glicose',
        'valor' => '92',
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuarioB->id}/lab-results"
    );

    $response->assertOk()
        ->assertJsonPath('data', []);
});

it('rejects list by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson(
        "/api/medical-records/{$prontuario->id}/lab-results"
    );

    $response->assertForbidden();
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->getJson(
        "/api/medical-records/{$prontuario->id}/lab-results"
    );

    $response->assertUnauthorized();
});

it('returns 404 for non-existent medical record', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson(
        '/api/medical-records/99999/lab-results'
    );

    $response->assertNotFound();
});
