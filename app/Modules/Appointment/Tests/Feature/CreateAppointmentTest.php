<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('creates an appointment as a doctor', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.doctor_id', $doctor->id)
        ->assertJsonPath('data.type', 'consultation')
        ->assertJsonPath('data.status', 'pending');
});

it('creates an appointment with a patient', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'patient_id' => $patient->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '14:00',
        'type' => 'follow_up',
        'notes' => 'Retorno de exames.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.patient_id', $patient->id);
});

it('rejects creation without required fields', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/appointments', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'time', 'type']);
});

it('rejects creation for unauthenticated user', function (): void {
    $response = $this->postJson('/api/appointments', [
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertUnauthorized();
});
