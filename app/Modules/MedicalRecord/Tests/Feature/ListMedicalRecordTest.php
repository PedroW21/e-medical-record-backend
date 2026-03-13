<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('lists medical records for a patient', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    Prontuario::factory()->count(3)->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/medical-records");

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'patient_id', 'doctor_id', 'type', 'status']], 'meta']);
});

it('filters by status', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    Prontuario::factory()->count(2)->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    Prontuario::factory()->finalized()->count(1)->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/medical-records?status=draft");

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $response->assertJsonPath('data.0.status', 'draft');
});

it('paginates results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    Prontuario::factory()->count(5)->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/medical-records?per_page=2&page=1");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5);
});

it('does not list records from another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    Prontuario::factory()->count(3)->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($otherDoctor)->getJson("/api/patients/{$patient->id}/medical-records");

    $response->assertNotFound();
});

it('returns 401 for unauthenticated user', function (): void {
    $patient = Paciente::factory()->create();

    $response = $this->getJson("/api/patients/{$patient->id}/medical-records");

    $response->assertUnauthorized();
});
