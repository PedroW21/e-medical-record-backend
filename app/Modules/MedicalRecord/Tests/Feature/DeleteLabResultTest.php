<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('deletes a lab result value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}"
    );

    $response->assertOk()
        ->assertJsonPath('message', 'Resultado laboratorial excluído com sucesso.');

    $this->assertDatabaseMissing('valores_laboratoriais', ['id' => $labValue->id]);
});

it('rejects delete on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}"
    );

    $response->assertStatus(409);
    $this->assertDatabaseHas('valores_laboratoriais', ['id' => $labValue->id]);
});

it('rejects delete by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}"
    );

    $response->assertForbidden();
    $this->assertDatabaseHas('valores_laboratoriais', ['id' => $labValue->id]);
});

it('rejects delete when lab value belongs to a different medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuarioB->id}/lab-results/{$labValue->id}"
    );

    $response->assertNotFound();
    $this->assertDatabaseHas('valores_laboratoriais', ['id' => $labValue->id]);
});

it('returns 404 for non-existent lab value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/99999"
    );

    $response->assertNotFound();
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}"
    );

    $response->assertUnauthorized();
});
