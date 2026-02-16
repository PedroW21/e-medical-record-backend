<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;

it('rejects appointment at conflicting time slot', function (): void {
    $doctor = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->create([
        'data' => $date,
        'horario' => '10:00',
        'status' => AppointmentStatus::Confirmed,
    ]);

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $date,
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('allows appointment at cancelled slot', function (): void {
    $doctor = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->cancelled()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $date,
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('allows appointment at requested slot', function (): void {
    $doctor = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->requested()->create([
        'data' => $date,
        'horario' => '11:00',
    ]);

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $date,
        'time' => '11:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('allows appointment at different time same day', function (): void {
    $doctor = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $date,
        'time' => '11:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});
