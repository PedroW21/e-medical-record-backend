<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;

it('shows a single appointment', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->create();

    $response = $this->actingAs($doctor)->getJson("/api/appointments/{$appointment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $appointment->id);
});

it('returns 404 for another doctor appointment', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor1)->create();

    $response = $this->actingAs($doctor2)->getJson("/api/appointments/{$appointment->id}");

    $response->assertNotFound();
});
