<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;

it('updates status from pending to confirmed', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->create([
        'status' => AppointmentStatus::Pending,
    ]);

    $response = $this->actingAs($doctor)->patchJson("/api/appointments/{$appointment->id}/status", [
        'status' => 'confirmed',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

it('updates status from requested to pending', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->requested()->create();

    $response = $this->actingAs($doctor)->patchJson("/api/appointments/{$appointment->id}/status", [
        'status' => 'pending',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'pending');
});

it('updates status to cancelled', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->confirmed()->create();

    $response = $this->actingAs($doctor)->patchJson("/api/appointments/{$appointment->id}/status", [
        'status' => 'cancelled',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});
