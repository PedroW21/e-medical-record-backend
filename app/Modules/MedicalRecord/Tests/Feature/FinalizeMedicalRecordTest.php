<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('finalizes a draft record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$record->id}/finalize");

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonPath('data.status', 'finalized');

    $this->assertDatabaseHas('prontuarios', [
        'id' => $record->id,
        'status' => 'finalized',
    ]);
});

it('rejects finalizing an already finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->finalized()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$record->id}/finalize");

    $response->assertForbidden();
});

it('rejects finalization by another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($otherDoctor)->postJson("/api/medical-records/{$record->id}/finalize");

    $response->assertNotFound();
});

it('returns 401 for unauthenticated user', function (): void {
    $record = Prontuario::factory()->create();

    $response = $this->postJson("/api/medical-records/{$record->id}/finalize");

    $response->assertUnauthorized();
});
