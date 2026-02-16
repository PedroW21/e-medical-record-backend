<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Patient\Models\Paciente;

it('updates an appointment date and time', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->create();
    $newDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($doctor)->putJson("/api/appointments/{$appointment->id}", [
        'date' => $newDate,
        'time' => '15:00',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.date', $newDate)
        ->assertJsonPath('data.time', '15:00');
});

it('updates appointment patient link', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->putJson("/api/appointments/{$appointment->id}", [
        'patient_id' => $patient->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.patient_id', $patient->id);
});

it('does not allow updating another doctor appointment', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor1)->create();

    $response = $this->actingAs($doctor2)->putJson("/api/appointments/{$appointment->id}", [
        'time' => '16:00',
    ]);

    $response->assertNotFound();
});
