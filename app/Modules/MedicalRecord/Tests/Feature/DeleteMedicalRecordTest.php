<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('deletes a draft record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/medical-records/{$record->id}");

    $response->assertOk()
        ->assertJsonPath('message', 'Prontuário excluído com sucesso.');

    $this->assertDatabaseMissing('prontuarios', ['id' => $record->id]);
});

it('rejects deletion of a finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->finalized()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/medical-records/{$record->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('prontuarios', ['id' => $record->id]);
});

it('rejects deletion by another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($otherDoctor)->deleteJson("/api/medical-records/{$record->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('prontuarios', ['id' => $record->id]);
});

it('returns 401 for unauthenticated user', function (): void {
    $record = Prontuario::factory()->create();

    $response = $this->deleteJson("/api/medical-records/{$record->id}");

    $response->assertUnauthorized();
});
